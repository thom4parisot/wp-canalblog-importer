<?php

class CanalblogImporterImporterCategories extends CanalblogImporterImporterBase
{
  public function dispatch()
  {
    if (!get_option('canalblog_importer_blog_uri'))
    {
      return false;
    }

    $this->arguments['categories'] = $this->getCategories();

    return true;
  }

  public function process()
  {
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
    $http = new Wp_HTTP();
    $result = $http->get(get_option('canalblog_importer_blog_uri').'/archives/');

    $dom = new DomDocument();
    $dom->preserveWhitespace = false;
    @$dom->loadHTML($result['body']);

    $xpath = new DOMXPath($dom);
    $categories = array();
    foreach ($xpath->query("//div[@class='blogbody']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/[^\/]+/index.html$#iU', $node->getAttribute('href')))
      {
        $categories[] = $node->textContent;
      }
    }

    unset($dom, $http);
    return $categories;
  }
}