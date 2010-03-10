<?php

class CanalblogImporterImporterPost extends CanalblogImporterImporterBase
{
  protected $uri, $id, $data;
  protected static $remote_storage_base_domain = 'http://storage.canalblog.com';
  protected static $media_pattern = array(
    'new' => array(
      'detection_pattern' => '#(http://storage.canalblog.com/[^_]+\.[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#^http://storage.canalblog.com/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixNew'),
    ),
    'old' => array(
      'detection_pattern' => '#(%canalblog_domain%/images/[^t][^\/]+\.[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#^%canalblog_domain%/images/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixOld'),
    ),
  );

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#dispatch()
   */
  public function dispatch()
  {
    if (!$this->uri)
    {
      return false;
    }

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
    $query = $this->getRemoteXpath($this->uri, "//div[@class='blogbody']");
    $dom = new DomDocument();
    $dom->appendChild($dom->importNode($query->item(0), true));

    $post_id = $this->savePost($dom);
    $this->saveComments($dom);
    $this->saveMedias($dom);

    return $post_id;
  }

  /**
   * Save post content
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param DomDocument $dom
   * @return Integer Post ID
   */
  public function savePost(DomDocument $dom)
  {
    $xpath = new DomXpath($dom);
    $data = array(
      'post_status' => 'publish',
    );

    /*
     * Initial stuff
     *
     * Original ID, date etc.
     */
    preg_match('#/(\d+)\.html$#U', $this->uri, $matches);
    $canalblog_id = $matches[1];

    $tmpdom = new DomDocument();
    $tmpdom->appendChild($tmpdom->importNode($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']")->item(0), true));
    $itemfooter = $tmpdom->saveHTML();
    preg_match('#archives/(?P<post_year>\d{4})/(?P<post_month>\d{2})/(?P<post_day>\d{2})/(?P<post_id>\d+).html$#U', $this->uri, $matches);
    extract($matches);
    unset($matches, $tmpdom);

    /*
     * Determining title
     */
    $data['post_title'] = trim($xpath->query("//div[@class='blogbody']/h3[1]")->item(0)->textContent);

    /*
     * Determining date
     *
     * @todo handle multiple date formats (now, default date formating)
     */
    preg_match('#(\d{2}:\d{2})#U', $itemfooter, $dates);
    $data['post_date'] = sprintf('%s-%s-%s %s:00', $post_year, $post_month, $post_day, $dates[1]);

    /*
     * Striping images attributes such as their size
     * Also centering them with WordPress CSS class
     */
    foreach ($dom->getElementsByTagName('img') as $imgNode)
    {
      foreach (self::$media_pattern as &$config)
      {
        $config['detection_pattern_inline'] = str_replace('%canalblog_domain%', get_option('canalblog_importer_blog_uri'), $config['detection_pattern_inline']);

        if (preg_match($config['detection_pattern_inline'], $imgNode->getAttribute('src')))
        {
          $imgNode->removeAttribute('height');
          $imgNode->removeAttribute('width');
          $imgNode->removeAttribute('border');
          $imgNode->setAttribute('alt', '');
          $imgNode->setAttribute('class', 'aligncenter size-medium');
          $imgNode->parentNode->removeAttribute('target');
        }
      }
    }

    /*
     * Determining content
     */
    preg_match('#<a name="\d+"></a>(.+)<div class="itemfooter">#sU', $dom->saveHTML(), $matches);
    $data['post_content'] = preg_replace('#^.+(\r|\n)#sU', '', trim($matches[1]));

    /*
     * Determining author
     */
    preg_match('#Post&eacute; par (.+) &agrave;#siU', $itemfooter, $matches);
    $author_name = $matches[1];
    $data['post_author'] = $this->getOrCreateAuthorByUsername($author_name);

    /*
     * Opened to comments + trackbacks
     */
    $data['comment_status'] = $xpath->query("//div[@class='blogbody']//form[@id='frmComment']")->length ? 'open' : 'close';
    $data['ping_status'] = $xpath->query("//div[@class='blogbody']//a[@title='Rétroliens']")->length ? 'open' : 'close';

    /*
     * Saving
     *
     * As for now, we don't save again an existing post
     */
    if ($post_id = post_exists($data['post_title'], '', $data['post_date']))
    {
      $data['ID'] = $post_id;
    }
    else
    {
      $post_id = wp_insert_post($data);
      $data['ID'] = $post_id;
    }

    /*
     * Post save extras
     */
    /*
     * Determining categories
     */
    $categories = array();
    foreach ($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']//a[@title='Autres messages dans cette catégorie']") as $category)
    {
      $categories[] = category_exists($category->textContent);
    }

    if (!empty($categories))
    {
      wp_set_post_categories($post_id, $categories);
    }

    /*
     * Determining tags
     */
    $tags = array();
    foreach ($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']//a[@rel='tag']") as $tag)
    {
      $tags[] = $tag->textContent;
    }

    if (!empty($tags))
    {
      wp_set_post_tags($post_id, implode(',', $tags));
    }

    /*
     * Saving some extra meta
     * - original ID
     * - original URI
     */
    add_post_meta($post_id, 'canalblog_id', $canalblog_id, true);
    add_post_meta($post_id, 'canalblog_uri', $this->uri, true);

    $this->data = $data;
    $this->id =   $post_id;

    return $post_id;
  }

  /**
   * Save comments from a post
   *
   * @author oncletom
   * @since 1.0
   * @version 2.0
   * @param DomDocument $dom
   */
  public function saveComments(DomDocument $dom)
  {
    if ($this->data['comment_status'] == 'closed')
    {
      return false;
    }

    /*
     * Canalblog is only in french, hopefully for us (and me...)
     */
    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR@euro', 'fr_FR', 'fr', 'french');
    $date_pattern = '%s %s %s %s:%s';


    $xpath = new DomXpath($dom);
    $tmpdom = new DomDocument();
    $tmpdom->appendChild($tmpdom->importNode($xpath->query("//div[@class='blogbody']")->item(0), true));
    list($tmp, $html_comments) = explode('<a id="comments">', $tmpdom->saveHTML());
    unset($tmpdom, $tmp);

    preg_match_all('#<a id="c\d+"></a>(.+)<div class="itemfooter">(.+)</div>#siU', $html_comments, $matches);
    $found_comments = $matches[0];
    unset($matches);

    if (empty($found_comments))
    {
      return 0;
    }

    $comments = get_comments(array('post_id' => $this->id));

    foreach ($found_comments as $commentHtml)
    {
      $commentDom = new DomDocument();
      $commentDom->preserveWhitespace = false;
      @$commentDom->loadHTML($commentHtml);
      $xpath = new DomXpath($commentDom);
      $commentNode = $commentDom->getElementsByTagName('body')->item(0);

      if ($xpath->query("a[@id]", $commentNode)->length === 0)
      {
        continue;
      }

      /*
       * Determining Canalblog comment ID
       */
      $canalblog_comment_id = $xpath->query("a[@id]", $commentNode)->item(0)->getAttribute('id');

      /*
       * Checking if it's already saved from import
       */
      foreach ($comments as $comment)
      {
        if (get_comment_meta($comment->comment_ID, 'canalblog_id', true) == $canalblog_comment_id)
        {
          continue;
        }
      }

      $data = array(
        'comment_approved' => 1,
        'comment_karma' => 1,
        'comment_post_ID' =>  $this->id,
        'comment_author_email' => 'nobody@canalblog',
        'comment_agent' => 'Canalblog Importer',
        'comment_author_IP' => '127.0.0.1',
        'comment_type' => 'comment',
        'comment_author_url' => '',
      );

      /*
       * Comment Title
       * We agregate it in comment
       */
      $tmpdom = new DomDocument();
      if ($titleNode = $xpath->query('h3', $commentNode)->item(0))
      {
        $tmpnode = $tmpdom->createElement('p');
        $tmpnode->appendChild($tmpdom->createElement('strong', esc_html($titleNode->textContent)));
        $tmpdom->appendChild($tmpnode);
      }

      /*
       * Comment content
       * It's basically all direct <p>
       */
      foreach ($xpath->query('p', $commentNode) as $comment_p)
      {
        $tmpdom->appendChild($tmpdom->importNode($comment_p, true));
      }
      $data['comment_content'] = $tmpdom->saveHTML();
      unset($tmpdom, $tmpnode);

      /*
       * Comment footer
       */
      $commentFooterNode = $xpath->query("div[@class='itemfooter']", $commentNode)->item(0);

      //happens rarely, don't know why
      if (null === $commentFooterNode)
      {
        continue;
      }

      /*
       * Comment author + URI + date
       */
      if ($uriNode = $xpath->query("a", $commentFooterNode)->item(0))
      {
        $data['comment_author_url'] = $uriNode->getAttribute('href');
      }
      unset($uriNode);

      preg_match('#^Posté par (?P<comment_author>.+), (?P<day>[^ ]+) (?P<month>[^ ]+) (?P<year>[^ ]+) à (?P<hour>[^:]+):(?P<minute>.+)$#iUs', trim($commentFooterNode->textContent), $matches);
      $matches['strptime'] = strptime(sprintf($date_pattern, $matches['day'], $matches['month'], $matches['year'], $matches['hour'], $matches['minute']), '%d %B %Y %H:%M');
      $matches['month'] = sprintf('%02s', $matches['strptime']['tm_mon'] + 1);

      $data['comment_author'] =   $matches['comment_author'];
      $data['comment_date'] =     sprintf('%s-%s-%s %s:%s:00', $matches['year'], $matches['month'], $matches['day'], $matches['hour'], $matches['minute']);
      $data['comment_date_gmt'] = $data['comment_date'];
      unset($matches);

      /*
       * Saving (only if not exists)
       */
      $data = wp_filter_comment($data);
      if (!comment_exists($data['comment_author'], $data['comment_date']))
      {
        $comment_id = wp_insert_comment($data);
        add_comment_meta($comment_id, 'canalblog_id', $canalblog_comment_id, true);
      }
    }

    /*
     * Recounting comments for this post
     */
    wp_update_comment_count_now($this->id);
  }

  /**
   * Save medias from a post
   *
   * Also alter content to make it points locally
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param DomDocument $dom
   */
  public function saveMedias(DomDocument $dom)
  {
    /*
     * Initialize WordPress importer
     */
    require_once ABSPATH.'wp-admin/import/wordpress.php';
    $wpImport = new WP_Import();
    $wpImport->fetch_attachments = true;

    $attachments = array();
    $remote_uris = array();
    $remote_uris_mapping = array();
    $post = get_post($this->id, ARRAY_A);

    /*
     * Looping on different patterns of medias
     * Canalblog changed its media pattern around 2006 june
     */
    foreach (self::$media_pattern as $type => &$config)
    {
      $config['detection_pattern'] = str_replace('%canalblog_domain%', get_option('canalblog_importer_blog_uri'), $config['detection_pattern']);
      preg_match_all($config['detection_pattern'], $post['post_content'], $matches);

      if (empty($matches) || empty($matches[0]))
      {
        continue;
      }

      $remote_uris = array_merge($remote_uris, $matches[1]);
      $remote_uris_mapping[$type] = $matches[1];
    }

    $remote_uris = array_unique($remote_uris);

    /*
     * No picture? No need to go furthermore
     */
    if (empty($remote_uris))
    {
      return false;
    }

    $upload = wp_upload_dir($post['post_date']);

    foreach ($remote_uris as $remote_uri)
    {
      /*
       * Checking it does not exists yet
       */
      $candidates = get_posts(array(
        'meta_key' =>   'canalblog_attachment_uri',
        'meta_value' => $remote_uri,
        'post_type' =>  'attachment',
      ));

      /*
       * Skipping the save
       */
      if (!empty($candidates))
      {
        continue;
      }

      /*
       * Saving attachment
       */
      $postdata = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_excerpt', 'post_title', 'post_status', 'post_name', 'comment_status', 'ping_status', 'guid', 'post_parent', 'menu_order', 'post_type', 'post_password');
      $postdata['post_parent'] =   $this->id;
      $postdata['post_date'] =     $post['post_date'];
      $postdata['post_date_gmt'] = $post['post_date_gmt'];
      $postdata['post_author'] =   $this->data['post_author'];

      $attachment_id = $wpImport->process_attachment($postdata, $remote_uri);
      add_post_meta($attachment_id, 'canalblog_attachment_uri', $remote_uri, true);
      $attachments[$remote_uri] = $attachment_id;
    }

    /*
     * URL mapping
     * Basically, we change the thumbnail URI by the medium size
     */
    $new_map = array();
    foreach ($wpImport->url_remap as $old_uri => $new_uri)
    {
      if (!preg_match('/\.thumbnail\.[^\.]+$/', $old_uri))
      {
        if ($old_uri)
        {
          $new_map[$old_uri] = $new_uri;
        }

        continue;
      }

      /*
       * Restore Canalblog thumbnail URI
       */
      $original_uri = str_replace('.thumbnail', '', $old_uri);

      foreach (self::$media_pattern as $type => &$config)
      {
        if (!isset($remote_uris_mapping[$type]) || !in_array($original_uri, $remote_uris_mapping[$type]))
        {
          continue;
        }

        $old_uri = call_user_func($config['thumbnail_replacement_callback'], $old_uri);
      }


      /*
       * Replace it by our own thumbnail URI
       */
      $size_pattern = '-%sx%s';
      $new_uri = str_replace(
        sprintf($size_pattern, intval(get_option('thumbnail_size_w')), intval(get_option('thumbnail_size_h'))),
        sprintf($size_pattern, intval(get_option('medium_size_w')), intval(get_option('medium_size_h'))),
        $new_uri
      );

      $image_data = image_downsize($attachments[$original_uri], 'medium');
      $new_map[$old_uri] = $image_data[0];
    }

    $wpImport->url_remap = $new_map;


    /*
     * Saving mapping
     */
    if (!empty($wpImport->url_remap))
    {
      $wpImport->backfill_attachment_urls();
    }
  }

  protected function thumbnailFilenameFixNew($uri)
  {
    return str_replace('.thumbnail', '_p', $uri);
  }

  protected function thumbnailFilenameFixOld($uri)
  {
    $base_uri = get_option('canalblog_importer_blog_uri').'/images/';
    $uri = str_replace('.thumbnail', '', $uri);
    $uri = str_replace($base_uri, $base_uri.'t-', $uri);

    return $uri;
  }

  /**
   * Retrieves or create a user upon its username
   *
   * @author oncletom
   * @protected
   * @since 1.0
   * @version 1.0
   * @param String $username
   * @return integer
   */
  protected function getOrCreateAuthorByUsername($username)
  {
    if ($user_infos = get_userdatabylogin($username))
    {
      return $user_infos->ID;
    }

    $data = array(
      'display_name' =>  $username,
      'role' =>          'author',
      'user_login' =>    $username,
      'user_pass' =>     wp_generate_password(),
      'user_url'  =>     'http://',
    );

    return wp_insert_user($data);
  }

  /**
   * Set the post Canalblog URI
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param String $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
}