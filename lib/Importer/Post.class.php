<?php

class CanalblogImporterImporterPost extends CanalblogImporterImporterBase
{
  protected $uri, $id, $data;
  protected static $media_pattern = array(
    'new' => array(
      'detection_pattern' => '#(http://(p\d+\.)?storage.canalblog.com/[^_]+(?:\.|_p\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#^http://(p\d+\.)?storage.canalblog.com/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixNew'),
    ),
    'old' => array(
      'detection_pattern' => '#(%canalblog_domain%/images/[^t][^\/]+(?:\.|t-\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#http://%canalblog_domain%/images/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixOld'),
    ),
    'storagev1' => array(
      'detection_pattern' => '#(%canalblog_domain%/images/[^t][^\/]+(?:\.|t-\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#storagev1/%canalblog_domain%/images/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixOld'),
    ),
  );

  protected static $filename_pattern = array(
    array(
      'filter' =>'\/t-(.+).(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'thumbnail',
    ),
    array(
      'filter' =>'\/(.+)_q.(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'thumbnail',
    ),
    array(
      'filter' =>'\/(.+).thumbnail.(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'thumbnail',
    ),
    array(
      'filter' =>'\/(.+)_p.(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'medium',
    ),
    array(
      'filter' =>'\/(.+).to_resize_\d+x\d+.(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'medium',
    ),
    array(
      'filter' =>'\/(.+)_o.(a?png|jpe?g|gif|webp|bmp)$',
      'size' => 'full',
    ),
  );

  public function __construct(CanalblogImporterConfiguration $configuration)
  {
    parent::__construct($configuration);

    $this->overwrite_contents = get_option('canalblog_overwrite_contents', 0);
    $this->comments_status =    get_option('canalblog_comments_status', 'open');
    $this->trackbacks_status =  get_option('canalblog_trackbacks_status', 'open');
  }

  public static function getMediaPattern($id) {
    return self::$media_pattern[$id];
  }

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

  public function getContentFromUri($uri) {
    $html = null;
    $dom = $this->getRemoteDomDocument($uri, $html);
    $xpath = new DOMXPath($dom);

    $dom = new DomDocument();
    $root = $dom->createElement('html');
    // import headers
    $root->appendChild($dom->importNode(
      $xpath->query("//head")->item(0),
      true
    ));
    // import content as body
    //
    $root->appendChild($dom->importNode(
      $xpath->query("//div[@id='content']")->item(0),
      true
    ));
    $dom->appendChild($root);
    $dom->saveHTML();

    return array('dom' => $dom, 'html' => $html);
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	$data = array();
    $remote = $this->getContentFromUri($this->uri);

    $data['post'] = $this->savePost($remote['dom']);
    $data['medias'] = $this->saveMedias(get_post($this->id, ARRAY_A));
    $data['comments'] = $this->savePaginatedComments($remote['dom'], $remote['html']);

    return $data;
  }

  public function getData() {
    return $this->data;
  }

  public function extractTitle($xpath) {
    $title = '';
    $attempt = $xpath->query("//div[@class='blogbody']//a[@rel='bookmark']");

    if ($attempt->length) {
      $title = $attempt->item(0)->getAttribute('title');
    }
    else {
      $title = $xpath->query("//div[@class='blogbody']//a[@name]/following-sibling::*")->item(0)->textContent;
    }

    return trim($title);
  }

  public function extractPostDate($xpath) {
    $dateResult = $xpath->query("//meta[@itemprop='url']");
    $timeResult = $xpath->query("//div[@itemtype='http://schema.org/Article']/div[@class='itemfooter']");

    // old blogs have another metadata
    if (!$dateResult->length) {
      $dateTimeResult = $xpath->query("//meta[@property='article:published_time']")->item(0)->getAttribute('content');

      return str_replace('T', ' ', $dateTimeResult);
    }

    // http://xxx/archives/2013/09/02/27910679.html
    preg_match('#\/(?P<year>\d{4})\/(?P<month>\d{2})\/(?P<day>\d{2})\/.+.html#U', $dateResult->item(0)->getAttribute('content'), $matches);
    extract($matches);

    extract(array('hour' => '09', 'minutes' => '00'));
    if ($timeResult->length) {
      // xxx à 10:35
      preg_match('# à (?P<hour>\d{2}):(?P<minutes>\d{2})#U', $timeResult->item(0)->textContent, $matches);
      extract($matches);
    }

    return sprintf('%s-%s-%s %s:%s', $year, $month, $day, $hour, $minutes);
  }

  public function extractPostAuthorName($xpath) {
    $result = $xpath->query("//*[@class='articlecreator' or @itemprop='creator']");

    if (!$result->length) {
      return 'admin';
    }

    return $result->item(0)->textContent;
  }

  public function extractPostContent($xpath) {
    $tmpDom = new DomDocument();
    $finder = new DomXpath($tmpDom);

    $result = $xpath->query("//div[@itemtype='http://schema.org/Article']")->item(0);
    $strategy = 'article';

    if (!$result) {
      $result = $xpath->query("//div[@itemprop='articleBody']")->item(0);
      $strategy = 'articleBody';
    }

    if (!$result) {
      throw new CanalblogImporterException(sprintf(__("Failed to identify blog content. Please inform the plugin author about it.", 'canalblog-importer')));
    }

    $result = $tmpDom->importNode($result, true);
    $tmpDom->appendChild($result);

    // remove footer and everything after
    $footer = $finder->query("//div[@class='itemfooter']");

    if ($footer->length) {
      $footer = $footer->item(0);

      $childCursor = 0;
      $parentNode = $footer->parentNode;
      $delete = false;

      while ($childCursor < $parentNode->childNodes->length) {
        $item = $parentNode->childNodes->item($childCursor);

        if ($item->isSameNode($footer)) {
          $delete = true;
        }

        if ($delete) {
          $parentNode->removeChild($item);
        }

        $childCursor++;
      }
    }

    // remove itemprops
    foreach($finder->query("//*[boolean(@itemprop)]") as $item) {
      if ($item->getAttribute('itemprop') === 'articleBody') {
        continue;
      }

      $item->parentNode->removeChild($item);
    }

    // 'article' Strategy:
    // we remove headlines because everything is mixed up
    // this is the case with the 'article' Strategy (body is not clearly identifiable)
    $articleId = $result->getAttribute('id');

    if ($articleId || $strategy === 'article') {
      $anchor = $finder->query("//a[@name='". $articleId ."']")->item(0);
      $parentNode = $anchor->parentNode;

      while (true) {
        $childNode = $parentNode->childNodes->item(0);

        // and we remove the next one as it's the real title
        if ($childNode->isSameNode($anchor)) {
          if ($parentNode->childNodes->item(1)->nodeName !== '#text') {
            $parentNode->removeChild($parentNode->childNodes->item(1));
          }
          else {
            $parentNode->removeChild($parentNode->childNodes->item(2));
          }

          break;
        }
        else {
          $parentNode->removeChild($childNode);
        }
      }
    }

    // remove attributes
    // it's ineffective with the 'articleBody' strategy
    $tmpDom->firstChild->removeAttribute('id');
    $tmpDom->firstChild->removeAttribute('itemscope');
    $tmpDom->firstChild->removeAttribute('itemtype');
    $tmpDom->firstChild->removeAttribute('itemref');
    $tmpDom->firstChild->removeAttribute('class');
    $tmpDom->firstChild->removeAttribute('data-edittype');

    return $tmpDom;
  }

  public function extractCommentsPagination(DomDocument $dom) {
    $finder = new DomXpath($dom);
    $uris = array();

    foreach ($finder->query("//a[contains(@href, '-0.html#comments')]") as $link) {
      if (is_numeric($link->textContent)) {
        array_push($uris, $link->getAttribute('href'));
      }
    }

    return array_unique($uris);
  }

  public function extractComments($xpath) {
    $comments = array();

    // article and articleBody strategies
    foreach ($xpath->query("//*[@itemprop='comment' or @class='comment_item']") as $commentNode) {
      if (!$commentNode->hasAttribute('data-cid')) {
        continue;
      }

      $data = array(
        '__comment_id' => $commentNode->getAttribute('data-cid'),
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
       * Content
       */
      $tmpdom = new DomDocument();
      $tmpNode = $tmpdom->importNode($commentNode, true);
      $tmpdom->appendChild($tmpNode);
      $finder = new DomXpath($tmpdom);

      foreach($finder->query("//h3") as $item) {
        $item->parentNode->removeChild($item);
      }

      foreach($finder->query("//*[@class='itemfooter']") as $item) {
        $item->parentNode->removeChild($item);
      }

      $data['comment_content'] = trim($tmpdom->textContent);
      unset($tmpdom, $tmpnode);

      /*
       * Author
       */
      $commentAuthor = $xpath->query("div[@class='itemfooter']/a", $commentNode);

      if ($commentAuthor->length) {
        $data['comment_author_url'] = $commentAuthor->item(0)->getAttribute('href');
        $data['comment_author'] = $commentAuthor->item(0)->textContent;
      }
      else {
        preg_match('#^Posté par (?P<author>[^,]+),#U', $xpath->query("div[@class='itemfooter']", $commentNode)->item(0)->textContent,  $matches);

        if (!empty($matches)) {
          $data['comment_author'] = $matches['author'];
        }
      }

      /*
       * Date
       */
      // Modern Mode
      $commentDate = $xpath->query("//div[@class='itemfooter']/*[@class='timeago']", $commentNode);

      if ($commentDate->length) {
        $data['comment_date'] = str_replace('T', ' ', $commentDate->item(0)->getAttribute('title'));
      }

      // Legacy Mode
      else {
        $originalLocale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US');
        $tmp = trim(str_replace(array("\r\n", "\r", "\n"), ' ', $xpath->query("div[@class='itemfooter']", $commentNode)->item(0)->textContent));
        $tmp = str_replace('  ', ' ', $tmp);
        preg_match('#, (le )?(?P<day>[^ ]+) (?P<month>[^ ]+) (?P<year>[^ ]+) (à|&agrave;?) (?P<hour>[^:]+):(?P<minute>.+)$#iUs', $tmp, $matches);

        $from = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
        $to = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
        $tmp = str_ireplace(
          $from,
          $to,
          sprintf('%s %s %s %s:%s', $matches['day'], $matches['month'], $matches['year'], $matches['hour'], $matches['minute'])
        );

        $matches['strptime'] = strptime($tmp, '%d %B %Y %H:%M');
        $matches['month'] = sprintf('%02s', $matches['strptime']['tm_mon'] + 1);

        $data['comment_date'] = sprintf('%s-%s-%s %s:%s:00', $matches['year'], $matches['month'], $matches['day'], $matches['hour'], $matches['minute']);
        setlocale(LC_ALL, $originalLocale);
      }

      $data['comment_date_gmt'] = $data['comment_date'];

      array_push($comments, $data);
    }

    return $comments;
  }

  public function extractMediaUris($html) {
    $remote_uris = array();
    $dom = $this->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    // Get storage hyperlinks
    foreach ($xpath->query("//a[contains(@href, 'canalblog.com/storagev1') or contains(@href, 'storage.canalblog.com') or contains(@href, 'canalblog.com/docs')]") as $link) {
      array_push($remote_uris, $link->getAttribute('href'));
    }

    // Get image sources
    foreach ($xpath->query("//img[contains(@src, 'canalblog.com/storagev1') or contains(@src, 'storage.canalblog.com') or contains(@src, 'canalblog.com/images')]") as $link) {
      array_push($remote_uris, $link->getAttribute('src'));
    }

    $remote_uris = array_unique($remote_uris);

    return array_map(array($this, 'cleanupMediaUri'), $remote_uris);
  }

  protected function cleanupMediaUri ($uri) {
    foreach (self::$filename_pattern as $pattern) {
      // $size, $filter
      extract($pattern);

      if (preg_match('#'.$filter.'#iU', $uri)) {
        return array('uri' => $uri, 'size' => $size, 'original_uri' => preg_replace('#'.$filter.'#iU', '/\\1.\\2', $uri));
      }
    }

    return array('uri' => $uri, 'original_uri' => $uri, 'size' => 'full');
  }

  public function isImageSrcPattern($src, $media_pattern, $host) {
    $hostname = parse_url($host, PHP_URL_HOST);
    $media_pattern['detection_pattern_inline'] = str_replace('%canalblog_domain%', $hostname, $media_pattern['detection_pattern_inline']);

    return preg_match($media_pattern['detection_pattern_inline'], $src) === 1;
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
    $stats = array('title' => $this->uri, 'status' => __('error', 'canalblog-importer'));

    /*
     * Initial stuff
     *
     * Original ID, date etc.
     */
    preg_match('#/(\d+)\.html$#U', $this->uri, $matches);
    $canalblog_id = $matches[1];

    /*
     * Determining title and date
     */
    $data['post_title'] = $this->extractTitle($xpath);
    $data['post_date'] = $this->extractPostDate($xpath);

    if (!$data['post_date']) {
      return $stats;
    }

    /*
     * Striping images attributes such as their size
     * Also centering them with WordPress CSS class
     */
    foreach ($dom->getElementsByTagName('img') as $imgNode)
    {
      foreach (self::$media_pattern as &$config)
      {
        if ($this->isImageSrcPattern($imgNode->getAttribute('src'), $config, get_option('canalblog_importer_blog_uri')))
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
    $data['post_content'] = trim($this->extractPostContent($xpath)->saveHTML());

    /*
     * Determining author
     */
    $author_name = $this->extractPostAuthorName($xpath);
    $data['post_author'] = $this->getOrCreateAuthorByUsername($author_name);

    /*
     * Opened to comments + trackbacks
     */
    $data['comment_status'] = $this->comments_status;
    $data['ping_status'] = $this->trackbacks_status;

    /*
     * Saving
     *
     * As for now, we don't save again an existing post
     */
    if ($post_id = post_exists($data['post_title'], '', $data['post_date']))
    {
      $data['ID'] = $post_id;
      $stats['status'] = __('skipped', 'canalblog-importer');

      if ($this->overwrite_contents)
      {
        wp_untrash_post($post_id);
        wp_update_post($data);
        $stats['status'] = __('overwritten', 'canalblog-importer');
      }

      $post_existed = true;
    }
    else
    {
      $post_id = wp_insert_post($data, true);
      $data['ID'] = $post_id;
      $stats['status'] = __('imported', 'canalblog-importer');
    }

    $stats['id'] = $data['ID'];
    $stats['title'] = $data['post_title'];

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
    $this->id = $post_id;

    return $stats;
  }

  public function savePaginatedComments(DomDocument $dom, $html)
  {
    $cumulatedStats = $this->saveComments($dom, $html);
    $commentsUris = $this->extractCommentsPagination($dom);

    foreach ($commentsUris as $uri) {
      $remote = $this->getContentFromUri($uri);
      $stats = $this->saveComments($remote['dom'], $remote['html']);

      $cumulatedStats['count'] += $stats['count'];
      $cumulatedStats['new'] += $stats['new'];
      $cumulatedStats['skipped'] += $stats['skipped'];
      $cumulatedStats['overwritten'] += $stats['overwritten'];
    }

    return $cumulatedStats;
  }

  /**
   * Save comments from a post
   *
   * @author oncletom
   * @since 1.0
   * @version 2.0
   * @param DomDocument $dom
   */
  public function saveComments(DomDocument $dom, $html)
  {
    $xpath = new DomXpath($dom);
  	$stats = array('count' => 0, 'new' => 0, 'skipped' => 0, 'overwritten' => 0);

    if ($this->data['comment_status'] == 'closed')
    {
      return $stats;
    }

    /*
     * Canalblog is only in french, hopefully for us (and me...)
     */
    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR@euro', 'fr_FR', 'fr', 'french');

    $found_comments = $this->extractComments($xpath);
 		$stats['count'] = count($found_comments);
    unset($matches);

    if (empty($found_comments)) {
      return $stats;
    }

    $comments = get_comments(array('post_id' => $this->id));

    foreach ($found_comments as $data) {
      $canalblog_comment_id = $data['__comment_id'];
      unset($data['__comment_id']);

      /*
       * Saving (only if not exists)
       */
      $data = wp_filter_comment($data);
      if ($comment_id = comment_exists($data['comment_author'], $data['comment_date']))
      {
        $data['comment_ID'] = $comment_id;

        if ($this->overwrite_contents)
        {
          if ('trash' === wp_get_comment_status($comment_id))
          {
            wp_untrash_comment($comment_id);
          }

          wp_update_comment($data);
          $stats['overwritten']++;
        }
        else
        {
        	$stats['skipped']++;
        }
      }
      else
      {
        $comment_id = wp_insert_comment($data);

        if ($comment_id === false) {
          throw new CanalblogImporterException(sprintf(__("Failed to execute query: %s", 'canalblog-importer'), $wpdb->last_error));
        }

        add_comment_meta($comment_id, 'canalblog_id', $canalblog_comment_id, true);
        $stats['new']++;
      }

      unset($tmp, $data);
    }

    /*
     * Recounting comments for this post
     */
    wp_update_comment_count_now($this->id);
    return $stats;
  }

  /**
   * Save medias from a post
   *
   * Also alter content to make it points locally
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   */
  public function saveMedias(array $post)
  {
  	$stats = array('count' => 0, 'new' => 0, 'skipped' => 0, 'overwritten' => 0, 'remap' => array());

    /*
     * Initialize WordPress importer
     */
    try{
      self::requireWordPressImporter($this->getConfiguration());
    }
    catch (CanalblogImporterException $e)
    {
      printf('wordpress-importer is missing');
      return $stats;
    }

    $wpImport = new WP_Import();
    $wpImport->fetch_attachments = true;

    $attachments = array();
    $remote_uris = array();
    $remote_uris_mapping = array();

    /*
     * Collecting attachment URIs
     */
    $remote_uris = $this->extractMediaUris($post['post_content']);
    $stats['count'] = count($remote_uris);

    /*
     * No attachment? We skip the rest
     */
    if (empty($remote_uris))
    {
      return $stats;
    }

    $upload = wp_upload_dir($post['post_date']);

    $attachments = $this->importAttachments($wpImport, $post, $remote_uris, $stats);
    $wpImport->url_remap = $this->updateAttachmentsRemap($attachments);
    $stats['remap'] = $wpImport->url_remap;

    /*
     * Saving mapping
     */
    $wpImport->backfill_attachment_urls();

    return $stats;
  }

  public function importAttachments(WP_Import &$wpImport, $post, array $remote_uris, &$stats) {
    $attachments = array();

    foreach ($remote_uris as $pair) {
        // $uri, $original_uri, $size
        extract($pair);

        /*
         * Checking it does not exists yet
         */
        $candidates = get_posts(array(
          'meta_key' =>   'canalblog_attachment_uri',
          'meta_value' => $original_uri,
          'post_type' =>  'attachment',
        ));

        /*
         * Skipping the save
         */
        if (!empty($candidates))
        {
        	$stats['skipped']++;
          $attachments[$uri] = array_merge($pair, array('id' => $candidates[0]->ID));
          continue;
        }

        /*
         * Saving attachment
         */
        $postdata = array();
        $postdata['guid'] = $original_uri;
        $postdata['post_parent'] = $post['ID'];
        $postdata['upload_date'] = $post['post_date'];
        $postdata['post_date'] = $post['post_date'];
        $postdata['post_date_gmt'] = $post['post_date_gmt'];
        $postdata['post_author'] = $post['post_author'];
        $postdata['post_title'] = '';

        $attachment_id = $wpImport->process_attachment($postdata, $original_uri);

        if ($attachment_id instanceof WP_Error) {
          $stats['error']++;
        }
        else {
          add_post_meta($attachment_id, 'canalblog_attachment_uri', $original_uri, true);
          $attachments[$uri] = array_merge($pair, array('id' => $attachment_id));
          $stats['new']++;
        }

      }

    return $attachments;
  }

  public function updateAttachmentsRemap($attachments) {
    $new_map = array();

    foreach ($attachments as $old_uri => $attachment) {
      // $uri, $original_uri, $id, $size
      extract($attachment);

      list($new_url, $width, $height) = image_downsize($id, $size);
      $new_map[ $old_uri ] = $new_url;
    }

    return $new_map;
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
    if ($user_infos = get_user_by('login', $username))
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
