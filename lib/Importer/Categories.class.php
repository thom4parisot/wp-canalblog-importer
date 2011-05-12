<?php

class CanalblogImporterImporterCategories extends CanalblogImporterImporterBase
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

    $this->arguments['categories'] = $this->getCategories();

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	return false;
  
    $counter = 0;
    foreach ($this->arguments['categories'] as $category)
    {
      wp_create_category($category);
      $counter++;
    }

    if ($counter === count($this->arguments['categories']))
    {
      return true;
    }
  }

  /**
   * Retrieves categories from Canalblog
   *
   * @author oncletom
   * @return unknown_type
   */
  protected function getCategories()
  {
    $dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/');
    $xpath = new DOMXPath($dom);
    $categories = array();

    foreach ($xpath->query("//div[@class='blogbody']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/[^\/]+/index.html$#iU', $node->getAttribute('href')))
      {
        $categories[] = $node->textContent;
      }
    }

    unset($dom, $xpath);
    return $categories;
  }
}