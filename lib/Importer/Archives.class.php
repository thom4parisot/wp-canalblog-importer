<?php

class CanalblogImporterImporterArchives extends CanalblogImporterImporterBase
{
  /**
   * @see lib/Importer/CanalblogImporterImporterBase#dispatch()
   */
  public function dispatch()
  {
    if (!get_option('canalblog_importer_blog_uri'))
    {
      return false;
    }

    $this->arguments['page'] =   get_option('canalblog_importer_archives_current_index', 0);
    $this->arguments['months'] = $this->getMonths();

    set_time_limit(60);
    ini_set('memory_limit', '128M');

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
    /*
     * No index defined? We can go the next step
     */
    if (empty($this->arguments['months']) || !isset($this->arguments['months'][get_option('canalblog_importer_archives_current_index')]))
    {
      return true;
    }

    $archives_index = $this->arguments['months'][get_option('canalblog_importer_archives_current_index')];
    $permalinks = $this->getMonth($archives_index['year'], $archives_index['month']);

    foreach ($permalinks as $permalink)
    {
      /*
       * Importing post content
       */
      $post = new CanalblogImporterImporterPost($this->getConfiguration());
      $post->setUri($permalink);
      $post->process();
    }

    update_option('canalblog_importer_archives_current_index', $this->arguments['page'] + 1);
    $url = '?import=canalblog&step='.get_option('canalblog_importer_step').'&process-import=1&_wpnonce='.wp_create_nonce('import-canalblog');
    echo '<script type="text/javascript">window.location.href = "'.$url.'";</script>';
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