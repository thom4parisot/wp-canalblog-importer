<?php
/*
Plugin Name: Canalblog Importer
Description: Enables content importing from a blog hosted on Canalblog platform.
Author: Oncle Tom
Version: 1.0.2
Text Domain: canalblog-importer
Domain Path: /i18n
Author URI: http://case.oncle-tom.net/
Plugin URI: http://wordpress.org/extend/plugins/canalblog-importer/

  This plugin is released under version 3 of the GPL:
  http://www.opensource.org/licenses/gpl-3.0.html
*/

/* needed to enable automatic extraction with Poedit for plugin listing */
__('Enables content importing from a blog hosted on Canalblog platform.', 'canalblog-importe');

if (phpversion() < '5.1')
{
  printf("Canalblog Importer nécessite PHP 5.1 ou plus (votre version : <code>%s</code>). <a href='http://www.wordpress-fr.net/support/sujet-31932-1.html' target='_blank'>Comment faire</a> ?", phpversion());
  die();
}

require dirname(__FILE__).'/lib/Plugin.class.php';

$CanalblogImporterPlugin = WPPluginToolkitPlugin::create('CanalblogImporter', __FILE__);
$CanalblogImporterPlugin->dispatch();
