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
    $data = array();

    /*
     * Initial stuff
     */
    $tmpdom = new DomDocument();
    $tmpdom->appendChild($tmpdom->importNode($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']")->item(0), true));
    $itemfooter = $tmpdom->saveHTML();
    preg_match('#archives/(?P<post_year>\d{4})/(?P<post_month>\d{2})/(?P<post_day>\d{2})/(?P<post_id>\d+).html$#U', $this->uri, $matches);
    extract($matches);
    unset($matches, $tmpdom);

    /*
     * Determining title
     */
    $data['post_title'] = $xpath->query("//div[@class='blogbody']/h3[1]")->item(0)->textContent;

    /*
     * Determining date
     */
    preg_match('#(\d{2}:\d{2})#U', $itemfooter, $dates);
    $data['post_date'] = sprintf('%s-%s-%s %s:00', $post_year, $post_month, $post_day, $dates[1]);

    /*
     * Determining content
     */
    preg_match('#<a name="\d+"></a>(.+)<div class="itemfooter">#sU', $dom->saveHTML(), $matches);
    $data['post_content'] = preg_replace('#^.+(\r|\n)#sU', '', trim($matches[1]));

    /*
     * Determining tags
     * ////div[@class='blogbody']//div[@class='itemfooter']//a[@rel='tag']
     */

    /*
     * Determining categories
     */

    /*
     * Determining author
     */

    /*
     * Saving
     */

    /*
     * Saving some extra meta
     * - original ID
     * - original URI
     */
  }

  public function setUri($uri)
  {
    $this->uri = $uri;
  }
}