<?php
/**
 * Importing class, calling to sub class for specialized tasks
 *
 * @author oncletom
 * @since 1.0
 */
class CanalblogImporterImporter
{
  protected $configuration;
  protected $current_page = 1;
  protected $pages = array(
    1 => array('page' => '01-init', 'operation' => 'CanalblogImporterImporterConfiguration'),
    2 => array('page' => '02-tags', 'operation' => 'CanalblogImporterImporterTags'),
    3 => array('page' => '03-cats', 'operation' => 'CanalblogImporterImporterCategories'),
    4 => array('page' => '04-arch', 'operation' => 'CanalblogImporterImporterArchives'),
    5 => array('page' => '05-post', 'operation' => 'CanalblogImporterImporterPosts'),
    6 => array('page' => '06-clnp', 'operation' => 'CanalblogImporterImporterCleanup'),
    7 => array('page' => '07-ccln', 'operation' => 'CanalblogImporterImporterConclusion'),
  );
  protected $plugin;
  protected $is_ready_to_process = false;

  /**
   * Setups the importer
   * @author oncletom
   * @param CanalblogImporterPlugin $plugin
   * @return unknown_type
   */
  public function __construct(CanalblogImporterPlugin $plugin)
  {
    $this->plugin = $plugin;
    $this->configuration = $plugin->getConfiguration();
  }

  /**
   * Determines at which step we are
   * 
   * @todo seperate the step getter
   * @return unknown_type
   */
  public function dispatch()
  {
    /*
     * Determines page ID
     */
    $this->current_page = $this->getCurrentPage();
    $operation = $this->getOperation();

    /*
     * Do we cancel sometime?
     */
    if (isset($_REQUEST['cancel'], $_REQUEST['_wpnonce']) && 1 === (int)$_REQUEST['cancel'] && wp_verify_nonce($_REQUEST['_wpnonce'], 'import-canalblog-cancel'))
    {
    	$this->stop();
      printf('<script type="text/javascript">document.location.href="%s";</script>',
      	get_admin_url(null, 'import.php?import=canalblog&canceled=1')
      );
    }

    $this->is_ready_to_process = !!$operation->dispatch();
    
    return $operation;
  }
  
  /**
   * Returns the current page identifier
   * 
   * @since 1.2
   * @return integer
   */
  public function getCurrentPage()
  {
  	$current_page = get_option('canalblog_importer_step', 1);
    
  	return $current_page ? $current_page : 1;
  }
  
  /**
   * Retrieves the current operation object
   * 
   * @since 1.2
   * @return CanalblogImporterImporterBase
   */
  public function getOperation()
  {
  	return new $this->pages[$this->getCurrentPage()]['operation']($this->configuration);
  }

  /**
   * Prints the page output
   *
   * @author oncletom
   * @param CanalblogImporterImporterBase $operation
   * @param Array $args
   */
  public function printPage(CanalblogImporterImporterBase $operation)
  {
    extract($operation->getArguments());

    if (!CanalblogImporterImporterBase::isWordPressImporterInstalled($this->configuration))
    {
      include $this->configuration->getDirname().'/pages/wordpress-importer-missing.php';
    }

    include $this->configuration->getDirname().'/pages/'.$this->pages[$this->current_page]['page'].'.php';
  }

  /**
   * Operates the current operation
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param CanalblogImporterImporterBase $operation
   */
  public function process(CanalblogImporterImporterBase $operation)
  {
    if (true === $this->is_ready_to_process && isset($_REQUEST['process-import']) && (int)$_REQUEST['process-import'] === 1 && wp_verify_nonce($_REQUEST['_wpnonce'], 'import-canalblog'))
    {
      if (!defined('WP_IMPORTING'))
      {
      	define('WP_IMPORTING', true);
      }

      try{
        $return = $operation->process();

        if ($return === true)
        {
          update_option('canalblog_importer_step', $this->current_page + 1);
          printf('<script type="text/javascript">document.location.href="%s";</script>',
          	get_admin_url(null, 'import.php?import=canalblog&step='.get_option('canalblog_importer_step'))
          );
        }
      }
      catch(CanalblogImporterException $e)
      {
        $e->rethrow();
      }
      catch(Exception $e)
      {
        echo 'An unknow exception occured';
        print $e;
      }
    }
  }

  /**
   * Cancel the current import
   *
   * @author oncletom
   * @since 1.0
   * @version 1.1
   * @return unknown_type
   */
  protected function stop()
  {
    delete_option('canalblog_importer_blog_uri');
    delete_option('canalblog_importer_step');
    delete_option('canalblog_importer_archives_current_index');
    delete_option('canalblog_overwrite_contents');
    delete_option('canalblog_comments_status');
    delete_option('canalblog_trackbacks_status');
    
    delete_transient('canalblog_tags');
    delete_transient('canalblog_categories');
    delete_transient('canalblog_months');
    delete_transient('canalblog_permalinks');
    delete_transient('canalblog_post_uris');
    
    delete_transient('canalblog_step_offset');
    delete_transient('canalblog_have_finished_tags');
    delete_transient('canalblog_have_finished_categories');
    delete_transient('canalblog_have_finished_archives');
    delete_transient('canalblog_have_finished_posts');
    delete_transient('canalblog_have_finished_cleanup');
  }
}