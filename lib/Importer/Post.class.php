<?php

class CanalblogImporterImporterPost extends CanalblogImporterImporterBase
{
  protected $uri;

  public function dispatch()
  {
    if (!$this->uri)
    {
      return false;
    }

    return true;
  }

  public function process()
  {
    $query = $this->getRemoteXpath($this->uri, "//div[@class='blogbody']");
    $dom = new DomDocument();
    $dom->appendChild($dom->importNode($query->item(0), true));

    $post = $this->savePost($dom);
    //$this->saveComments();
    //$this->saveMedias();
  }

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
      //return $post_id;
    }

    $post_id = wp_insert_post($data);

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

    return $post_id;
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