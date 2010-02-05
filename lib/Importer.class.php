<?php

class CanalblogImporterImporter
{
  protected $configuration;
  protected $current_page = 1;
  protected $pages = array(
    1 => array('page' => '01-init', 'operation' => 'CanalblogImporterImporterConfiguration'),
    2 => array('page' => '02-tags', 'operation' => 'CanalblogImporterImporterTags'),
    3 => array('page' => '03-cats', 'operation' => 'CanalblogImporterImporterCategories'),
    4 => array('page' => '04-arch', 'operation' => 'CanalblogImporterImporterArchives'),
    5 => array('page' => '05-clnp', 'operation' => 'CanalblogImporterImporterCleanup'),
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
   * @return unknown_type
   */
  public function dispatch()
  {
    /*
     * Determines page ID
     */
    $current_page = get_option('canalblog_importer_step', 1);
    $current_page = (int)$current_page ? $current_page : 1;
    $this->current_page = $current_page;
    $operation = new $this->pages[$current_page]['operation']($this->configuration);

    $this->is_ready_to_process = !!$operation->dispatch();

    return $operation;
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
    if (true === $this->is_ready_to_process && isset($_POST) && !empty($_POST) && check_admin_referer('import-canalblog'))
    {
      try{
        $return = $operation->process();

        if ($return === true)
        {
          update_option('canalblog_importer_step', $this->current_page + 1);
          echo '<script type="text/javascript">window.location.href="?import=canalblog&step='.get_option('canalblog_importer_step').'";</script>';
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
   * @version 1.0
   * @return unknown_type
   */
  protected function stop()
  {
    delete_option('canalblog_importer_blog_uri');
    delete_option('canalblog_importer_step');
    wp_redirect('?import=canalblog');
  }
}