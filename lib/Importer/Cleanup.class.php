<?php
/**
 * Cleanup last things
 *
 * @author oncletom
 * @since 1.0
 */
class CanalblogImporterImporterCleanup extends CanalblogImporterImporterBase
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
  	if (!!get_transient('canalblog_have_finished_cleanup'))
  	{
  		delete_transient('canalblog_have_finished_cleanup');

  		return true;
  	}

    return false;
  }

  public function processRemote(WP_Ajax_Response $response)
  {
    /*
     * Retrieves posts IDs
     */
    global $wpdb;
    $replacements = array();

    $sql = $wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", 'canalblog_uri');
    foreach ($wpdb->get_results($sql) as $meta)
    {
      $replacements[$meta->meta_value] = get_permalink($meta->post_id);
    }

    uksort($replacements, array($this, 'cmpr_strlen'));
    foreach ($replacements as $old_uri => $new_uri)
    {
      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '%s', '%s')", $old_uri, $new_uri));

      if ($wpdb->last_error) {
        throw new CanalblogImporterException(sprintf(__("Failed to execute query: %s", 'canalblog-importer'), $wpdb->last_error));
      }
    }

    $response->add(array(
  		'data' => sprintf(__('%s posts cleanup up.', 'canalblog-importer'), count($replacements)),
  	));

  	$this->setProcessFinished('canalblog_have_finished_cleanup');
  	$this->processRemoteShutdown($response);
  }

  // sort by strlen, longest string first
  function cmpr_strlen($a, $b) {
    return strlen($b) - strlen($a);
  }
}
