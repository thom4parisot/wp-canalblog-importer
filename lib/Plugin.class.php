<?php
if (!class_exists('BasePlugin'))
{
  require dirname(__FILE__).'/vendor/plugin-toolkit/BasePlugin.class.php';
}

/**
 * Canalblog Plugin class
 *
 * @author oncletom
 * @package canalblog-importer
 * @subpackage lib
 */
class CanalblogImporterPlugin extends WPPluginToolkitPlugin
{
  protected $is_filters_registered = false;
  protected $is_hooks_registered = false;


  /**
   * Method to register hooks (and do it only once)
   *
   * @author oncletom
   * @since 1.1
   * @version 1.1
   * @return Boolean
   */
  public function dispatch()
  {
    if ($this->is_hooks_registered)
    {
      return false;
    }

    add_action('admin_init', array($this, 'registerAdminHooks'));

    return $this->is_hooks_registered = true;
  }

  /**
   * Adds some metas within the plugin list
   *
   * @since 1.0.2
   * @version
   * @param Array $plugin_meta
   * @param String $plugin_file
   * @param Array $plugin_data
   * @return Array
   */
  public function filterPluginRowMeta($plugin_meta, $plugin_file, $plugin_data)
  {
    if ($plugin_data['Name'] === 'Canalblog Importer')
    {
      $plugin_meta[] = '<a href="import.php?import=canalblog">'.__('Import a Canalblog blog', 'canalblog-importer').'</a>';
    }

    return $plugin_meta;
  }

  /**
   * Prints out the import page for WordPress
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   */
  public function importPage()
  {
  	deactivate_plugins('wordpress-importer/wordpress-importer.php', true);
    $importer = new CanalblogImporterImporter($this);

    $operation = $importer->dispatch();
    $importer->process($operation);
    //$importer->preProcess($operation);
    $importer->printPage($operation);
  }
  
  /**
   * Executes the real stuff through AJAX
   * 
   * @since 1.2
   */
  public function processRemoteOperation()
  {
  	$response = new WP_Ajax_Response();
  	
  	$importer = new CanalblogImporterImporter($this);
  	$operation = $importer->dispatch();
 
  	if (wp_verify_nonce($_REQUEST['_wpnonce'], 'import-canalblog'))
  	{
      if (!defined('WP_IMPORTING'))
      {
      	define('WP_IMPORTING', true);
      }

  		$operation->processRemote($response);
  	}
  	
  	$response->send();
  	exit;
  }

  /**
   * Register hooks on admin initialization
   *
   * @author oncletom
   * @since 1.0
   * @version 1.1
   */
  public function registerAdminHooks()
  {
    register_importer('canalblog', __('Canalblog'), __('Import posts, comments, and users from a Canalblog blog.', 'canalblog-importer'), array ($this, 'importPage'));
    add_action('admin_enqueue_scripts', array($this, 'registerAssets'));
    add_action('wp_ajax_canalblog_import_remote_operation', array($this, 'processRemoteOperation'));
    add_filter('plugin_row_meta', array($this, 'filterPluginRowMeta'), 10, 3);
  }
  
  /**
   * JavaScripts registration, if needed
   * 
   * @since 1.2
   * @version 1.0
   */
  public function registerAssets()
  {
  	if (isset($_GET['import']) && 'canalblog' === $_GET['import'])
  	{
  		 wp_enqueue_script('canalblog_importer', $this->configuration->getPluginUri().'/assets/javascripts/import.js', array('jquery'), CanalblogImporterConfiguration::VERSION);
  		 wp_enqueue_style('canalblog_importer', $this->configuration->getPluginUri().'/assets/stylesheets/import.css', array(), CanalblogImporterConfiguration::VERSION);
  	}
  }
}
