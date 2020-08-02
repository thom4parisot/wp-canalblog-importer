<?php
/**
 * Setups the configuration for importing
 *
 * @author oncletom
 * @since 1.0
 */
class CanalblogImporterImporterConfiguration extends CanalblogImporterImporterBase
{
  /**
   * @see lib/Importer/CanalblogImporterImporterBase#dispatch()
   */
  public function dispatch()
  {
    return true;
  }

  public function cleanUri($input) {
    $uri = esc_url_raw(strtolower(trim($input)), array('http', 'https'));
    $parts = parse_url($uri);

    if (!$uri) {
      return null;
    }

    return $parts['scheme'] . '://' . $parts['host'];
  }

  public function assertCanalblogByUri($uri) {
    return preg_match('#http://[^\.]+.canalblog.com#U', $uri) === 1;
  }

  public function assertCanalblogByHtml($html) {
    $dom = $this->getDomDocumentFromHtml($html);
    $xpath = $xpath = new DOMXPath($dom);

    $result = $xpath->query("//meta[@name='generator']");

    return $result && $result->length && strpos($result->item(0)->getAttribute('content'), 'CanalBlog') === 0;
  }



  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
    if (isset($_POST['blog_url']))
    {
      if (empty($_POST['blog_url']))
      {
        return false;
      }

      $uri = $this->cleanUri($_POST['blog_url']);

      try{
        if ($this->assertCanalblogByUri($uri) || $this->assertCanalblogByHtml($this->getRemoteHtml($uri)))
        {
          update_option('canalblog_importer_blog_uri', $uri);
          update_option('canalblog_overwrite_contents', isset($_POST['overwrite_contents']) ? 1 : 0);
          update_option('canalblog_comments_status', isset($_POST['comments_status']) && in_array($_POST['comments_status'], array('open', 'closed')) ? $_POST['comments_status'] : 'open');
          update_option('canalblog_trackbacks_status', isset($_POST['trackbacks_status']) && in_array($_POST['trackbacks_status'], array('open', 'closed')) ? $_POST['trackbacks_status'] : 'open');

          return true;
        }
      }
      catch (CanalblogImporterException $e)
      {
        echo $e;
      }
      catch (Exception $e)
      {
        wp_die($e->getMessage());
      }

      return false;
    }
  }
}
