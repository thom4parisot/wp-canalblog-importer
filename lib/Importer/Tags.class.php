<?php
/**
 * Import tags from a remote blog
 *
 * @author oncletom
 * @since 1.0
 */
class CanalblogImporterImporterTags extends CanalblogImporterImporterBase
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

    $this->arguments['tags'] = $this->getTags();

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
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

  /**
   * Retrieves the tags from remote blog
   *
   * @author oncletom
   * @protected
   * @return Array
   */
  protected function getTags()
  {
    $dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/');
    $xpath = new DOMXPath($dom);
    $tags = array();

    foreach ($xpath->query("//div[@class='blogbody']//ul[@class='taglist']//a[@rel='tag']") as $node)
    {
      $tags[] = $node->nodeValue;
    }

    unset($dom, $xpath);
    return $tags;
  }
}