<?php

// include ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
// include ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

class ImportArchives extends WP_UnitTestCase {

  protected $importer;
  protected $operation;

  public function setUp() {
    $plugin = WPPluginToolkitPlugin::create('CanalblogImporter', dirname(__FILE__) . '/../bootstrap.php');

    $this->plugin = $plugin;
    $this->importer = new CanalblogImporterImporter($plugin);
    $this->operation = new CanalblogImporterImporterArchives($plugin->getConfiguration());
  }


	/**
   * @dataProvider archivesProvider
   * @group archives
   * @group realdata
	 */
	public function testGetMonths($url) {
    update_option('canalblog_importer_blog_uri', $url);
    $months = $this->operation->getMonths();

    $this->assertContains([ "year" => "2016", "month" => "01" ], $months);
	}

  public function archivesProvider() {
    return [
      [
        'http://grisfluo.canalblog.com'
      ],
    ];
  }

	/**
   * @depends testGetMonths
   * @group archives
   * @group realdata
	 */
	public function testGetMonthPermalinks() {
    $months = $this->operation->getMonths();
    $permalinks = array();

    foreach ($months as ['year' => $year, 'month' => $month]) {
      $month_permalinks = $this->operation->getMonthPermalinks($year, $month);
      $permalinks = array_merge($permalinks, $month_permalinks);
      $permalinks = array_unique($permalinks);
    }

    foreach ($permalinks as $uri) {
      $post = new CanalblogImporterImporterPost($this->plugin->getConfiguration());
      $post->setUri($uri);
      $data = $post->process();
    }
	}
}
