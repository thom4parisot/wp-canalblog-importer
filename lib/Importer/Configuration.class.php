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

      $uri = esc_url_raw(strtolower($_POST['blog_url']), array('http'));
      $uri = preg_replace('/(canalblog.com).*$/siU', '\\1', $uri);

      try{
        if (preg_match('#http://[^\.]+.canalblog.com#U', $uri) && $this->getRemoteHtml($uri))
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