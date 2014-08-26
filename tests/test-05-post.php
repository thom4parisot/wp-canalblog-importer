<?php

class ImportPort extends WP_UnitTestCase {

  protected $importer;
  protected $operation;

  public function setUp() {
    $plugin = WPPluginToolkitPlugin::create('CanalblogImporter', __DIR__ . '/../bootstrap.php');

    $this->importer = new CanalblogImporterImporter($plugin);
    $this->operation = new CanalblogImporterImporterPost($plugin->getConfiguration());
	  $this->operation->setUri('http://maflo.canalblog.com/archives/2010/09/07/18732506.html');
  }

  /*
   getContentFromUri()
   */
	function testGetContentFromUri() {
	  $result = $this->operation->getContentFromUri('http://www.boiremanger.net/archives/2014/08/24/30467749.html');

		$this->assertInstanceOf('DomDocument', $result['dom']);
		$this->assertInternalType('string', $result['html']);
	}

	/*
	 extractTitle()
	 */
	function testExtractTitleFromH3() {
	  $html = file_get_contents(__DIR__ . '/fixtures/post-maflo.html');
	  $dom = $this->operation->getDomDocumentFromHtml($html);
	  $xpath = new DomXpath($dom);

	  $this->assertEquals('personne ne veut de mes places de cinéma à gagner ????', $this->operation->extractTitle($xpath));
	}

	function testExtractTitleFromBookmarkTitle() {
	  $html = file_get_contents(__DIR__ . '/fixtures/post-couturejulie.html');
	  $dom = $this->operation->getDomDocumentFromHtml($html);
	  $xpath = new DomXpath($dom);

	  $this->assertEquals('Je déménage...', $this->operation->extractTitle($xpath));
	}

	/*
	 isImageSrcPattern()
	 */
	function testMediaHostedOnLegacyStaticCanalblog() {
	  $src = 'http://fushigiyugi.canalblog.com/images/fushigi_yugi_nuriko__manga__051.jpg';
	  $media_pattern = CanalblogImporterImporterPost::getMediaPattern('old');

	  $this->assertTrue($this->operation->isImageSrcPattern($src, $media_pattern, 'http://fushigiyugi.canalblog.com'));
	}

	function testMediaHostedOnStaticCanalblog() {
	  $src = 'http://static.canalblog.com/storagev1/fushigiyugi.canalblog.com/images/fushigi_yugi_nuriko__manga__051.jpg';
	  $media_pattern = CanalblogImporterImporterPost::getMediaPattern('storagev1');

	  $this->assertTrue($this->operation->isImageSrcPattern($src, $media_pattern, 'http://fushigiyugi.canalblog.com'));
	}

	function testMediaHostedStorageCanalblog() {
	  $src = 'http://p5.storage.canalblog.com/51/41/71856/22535465_p.jpg';
	  $media_pattern = CanalblogImporterImporterPost::getMediaPattern('new');

	  $this->assertTrue($this->operation->isImageSrcPattern($src, $media_pattern, 'http://www.boiremanger.net'));
	}

	function testMediaHostedOldStorageCanalblog() {
	  $src = 'http://storage.canalblog.com/51/41/71856/22535465_p.jpg';
	  $media_pattern = CanalblogImporterImporterPost::getMediaPattern('new');

	  $this->assertTrue($this->operation->isImageSrcPattern($src, $media_pattern, 'http://www.boiremanger.net'));
	}
}

