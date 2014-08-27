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
//	function testGetContentFromUri() {
//	  $result = $this->operation->getContentFromUri('http://www.boiremanger.net/archives/2014/08/24/30467749.html');
//
//		$this->assertInstanceOf('DomDocument', $result['dom']);
//		$this->assertInternalType('string', $result['html']);
//	}

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

	/**
   * @dataProvider mediaPatternProvider
   */
	function testGetMediaPattern($src, $patternId, $domain) {
	  $media_pattern = CanalblogImporterImporterPost::getMediaPattern($patternId);

	  $this->assertTrue($this->operation->isImageSrcPattern($src, $media_pattern, $domain));
	}

	public function mediaPatternProvider(){
    return array(
      array('http://fushigiyugi.canalblog.com/images/fushigi_yugi_nuriko__manga__051.jpg', 'old', 'http://fushigiyugi.canalblog.com'),
      array('http://static.canalblog.com/storagev1/fushigiyugi.canalblog.com/images/fushigi_yugi_nuriko__manga__051.jpg', 'storagev1', 'http://fushigiyugi.canalblog.com'),
      array('http://p5.storage.canalblog.com/51/41/71856/22535465_p.jpg', 'new', 'http://www.boiremanger.net'),
      array('http://storage.canalblog.com/51/41/71856/22535465_p.jpg', 'new', 'http://www.boiremanger.net')
    );
	}

	/**
   * @dataProvider extractPostDateProvider
   */
	public function testExtractPostDate($contentId, $expected) {
    $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $this->assertEquals($expected, $this->operation->extractPostdate($xpath));
	}

	public function extractPostDateProvider() {
	  return array(
	    array('boiremanger', '2014-08-24 19:09'),
	    array('couturejulie', '2013-01-02 14:18'),
	    array('maflo', '2010-08-03 11:41')
	  );
	}

  /**
   * @dataProvider extractPostAuthorNameProvider
   */
	public function testExtractPostAuthorName($contentId, $expected) {
    $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $this->assertEquals($expected, $this->operation->extractPostAuthorName($xpath));
	}

	public function extractPostAuthorNameProvider() {
	  return array(
	    array('boiremanger', 'admin'),
	    array('couturejulie', 'lacouturedejulie'),
	    array('maflo', 'maflo')
	  );
	}

	/**
	 * @dataProvider extractPostContentProvider
	 */
	public function testExtractPostContent($contentId, $startWith) {
    $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $this->assertStringStartsWith($startWith, trim($this->operation->extractPostContent($xpath)->textContent));
	}

	public function extractPostContentProvider() {
	  return array(
	    array('boiremanger', 'Les pigeons (3 pièces pour 6 personnes)'),
	    array('couturejulie', 'et oui fini le rose bonbon et les rayures...'),
	    array('maflo', "ou y'a vraiment plus personne qui attérit sur mon blog????"),
	  );
	}

	/**
   * @dataProvider postContentProvider
   */
	function testSavePost($contentId, $uri, $title) {
	  $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
	  $dom = $this->operation->getDomDocumentFromHtml($html);

	  $this->operation->setUri($uri);
	  $result = $this->operation->savePost($dom, $html);

	  $this->assertInternalType('integer', $result['id']);
	  $this->assertEquals('imported', $result['status']);
	  $this->assertEquals($title, $result['title']);
	}

	public function postContentProvider() {
	  return array(
	    array('boiremanger', 'http://www.boiremanger.net/archives/2014/08/24/30467749.html', 'Pigeon en deux cuissons, purée de céleri rôti, coulis de framboises, croustillants aux cèpes et cacao'),
	    array('couturejulie', 'http://lacouturedejulie.canalblog.com/archives/2013/01/02/26050095.html', 'Je déménage...'),
	    array('maflo', 'http://maflo.canalblog.com/archives/2010/09/07/18732506.html', 'personne ne veut de mes places de cinéma à gagner ????')
	  );
	}
}

