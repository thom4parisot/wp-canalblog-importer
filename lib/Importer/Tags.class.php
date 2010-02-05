<?php

class CanalblogImporterImporterTags extends CanalblogImporterImporterBase
{
  public function dispatch()
  {
    if (!get_option('canalblog_importer_blog_uri'))
    {
      return false;
    }

    $this->arguments['tags'] = $this->getTags();

    return true;
  }

  public function process()
  {
    $counter = 0;
    foreach ($this->arguments['tags'] as $tag)
    {
      wp_create_term($tag);
      $counter++;
    }

    if ($counter === count($this->arguments['tags']))
    {
      return true;
    }
  }

  protected function getTags()
  {
    $http = new Wp_HTTP();
    $result = $http->get(get_option('canalblog_importer_blog_uri').'/archives/');

    $dom = new DomDocument();
    $dom->preserveWhitespace = false;
    @$dom->loadHTML($result['body']);

    $xpath = new DOMXPath($dom);
    $tags = array();
    foreach ($xpath->query("//div[@class='blogbody']//ul[@class='taglist']//a[@rel='tag']") as $node)
    {
      $tags[] = $node->nodeValue;
    }

    unset($dom, $http);
    return $tags;
  }
}