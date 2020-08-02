<?php
/*
Plugin Name: Canalblog Importer
Description: Enables content importing from a blog hosted on Canalblog platform.
Author: Oncle Tom
Version: 1.6.5
Text Domain: canalblog-importer
Domain Path: /i18n
Author URI: http://case.oncle-tom.net/
Plugin URI: http://wordpress.org/extend/plugins/canalblog-importer/

  This plugin is released under version 3 of the GPL:
  http://www.opensource.org/licenses/gpl-3.0.html
*/

/* needed to enable automatic extraction with Poedit for plugin listing */
__('Enables content importing from a blog hosted on Canalblog platform.', 'canalblog-importe');

if (phpversion() < '7.1')
{
  printf("Canalblog Importer nécessite PHP 7.1 ou plus (votre version : <code>%s</code>).", phpversion());
  exit;
}

if (preg_match('#^WIN#iU', php_uname('s')) || !function_exists('strptime'))
{
  printf("Canalblog Importer ne fonctionne pas sous Windows ou sur les sytèmes auxquels il manque la fonction <code>strptime</code>.");
  exit;
}

require dirname(__FILE__).'/lib/Plugin.class.php';

$CanalblogImporterPlugin = WPPluginToolkitPlugin::create('CanalblogImporter', __FILE__);
$CanalblogImporterPlugin->dispatch();
