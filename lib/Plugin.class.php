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
   * Prints out the import page for WordPress
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   */
  public function importPage()
  {
    $importer = new CanalblogImporterImporter($this);

    $operation = $importer->dispatch();
    $importer->printPage($operation);
    $importer->process($operation);
  }

  /**
   * Register hooks on admin initialization
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   */
  public function registerAdminHooks()
  {
    register_importer('canalblog', __('Canalblog'), __('Import posts, comments, and users from a Canalblog blog.', 'canalblog-importer'), array ($this, 'importPage'));
  }
}
