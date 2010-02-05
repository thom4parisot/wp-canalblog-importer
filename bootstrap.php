<?php
/*
Plugin Name: Canalblog Importer
Description: Enables content importing from a blog hosted on Canalblog platform.
Author: Oncle Tom
Version: 1.0-dev
Author URI: http://case.oncle-tom.net/
Plugin URI: http://wordpress.org/extend/plugins/canalblog-importer/

  This plugin is released under version 3 of the GPL:
  http://www.opensource.org/licenses/gpl-3.0.html
*/

require dirname(__FILE__).'/lib/Plugin.class.php';

$CanalblogImporterPlugin = WPPluginToolkitPlugin::create('CanalblogImporter', __FILE__);
$CanalblogImporterPlugin->dispatch();
