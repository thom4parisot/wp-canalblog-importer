<?php

class CanalblogImporterConfiguration extends WPPluginToolkitConfiguration
{
  /**
   * Refers to the name of the plugin
   */
  const UNIX_NAME = 'canalblog-importer';

  /**
   * Refers to the version of the plugin
   */
  const VERSION =   '1.6.5';

  protected $wordpress_importer_locations = array();

  /**
   * @see lib/vendor/plugin-toolkit/WPPluginToolkitConfiguration#configureOptions()
   */
  protected function configureOptions()
  {
    $this->wordpress_importer_locations = array(
      $this->getDirname() . '/vendor/wordpress/wordpress-importer/wordpress-importer.php',
      WP_PLUGIN_DIR.'/wordpress-importer/wordpress-importer.php'
    );
  }

  /**
   * Returns the possible locations of WordPress Importer
   *
   * @since 1.1.4
   * @return array
   */
  public function getWordPressImporterLocations()
  {
    return $this->wordpress_importer_locations;
  }
}
