<?php

class CanalblogImporterImporterArchives extends CanalblogImporterImporterBase
{
  public function dispatch()
  {
    if (!get_option('canalblog_importer_blog_uri'))
    {
      return false;
    }

    $this->arguments['page'] =   isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $this->arguments['months'] = $this->getMonths();

    if ($this->arguments['page'] < 1 || ($this->arguments['page']-1 > count($this->arguments['months'])))
    {
      return false;
    }

    return true;
  }

  public function process()
  {
    $archives_index = $this->arguments['months'][$this->arguments['page'] - 1];
    $permalinks = $this->getMonth($archives_index['year'], $archives_index['month']);

    foreach ($permalinks as $permalink)
    {
      $post = new CanalblogImporterImporterPost($this->getConfiguration());
      $post->setUri($permalink);
      $post->process();
    }
  }

  /**
   * Retrieves all permalinks within a month archive
   * @param unknown_type $year
   * @param unknown_type $month
   * @return unknown_type
   */
  protected function getMonth($year, $month)
  {
    $uri_suffix = sprintf('%s/%s/index.html', $year, $month);
    $dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/'.$uri_suffix);
    $xpath = new DOMXPath($dom);
    $permalinks = array();

    foreach ($xpath->query("//div[@class='blogbody']//a[.='#']") as $node)
    {
      $permalinks[] = $node->getAttribute('href');
    }

    /*
     * Collecting other pages
     * Skipping first link and next page
     */
    foreach ($xpath->query("//div[@class='blogbody']//div[last()]//a[position()>1 and position()<last()]") as $node)
    {
      foreach ($this->getRemoteXpath($node->getAttribute('href'), "//div[@class='blogbody']//a[.='#']") as $node)
      {
        $permalinks[] = $node->getAttribute('href');
      }
    }

    return $permalinks;
  }

  /**
   * Retrieves categories from Canalblog
   *
   * @author oncletom
   * @return unknown_type
   */
  protected function getMonths()
  {
    foreach ($this->getRemoteXpath(get_option('canalblog_importer_blog_uri').'/archives/', "//div[@class='blogbody']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/(\d{4})/(\d{2})/index.html$#iU', $node->getAttribute('href'), $matches))
      {
        $months[] = array('year' => $matches[1], 'month' => $matches[2]);
      }
    }

    unset($dom, $http);
    return $months;
  }
}