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
	    array('boiremanger', '2014-08-23 06:55'),
	    array('couturejulie', '2013-01-02 14:18'),
	    array('maflo', '2010-08-03 11:41'),
	    array('masbou', '2014-07-15 07:53'),
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
	    array('maflo', 'maflo'),
	    array('masbou', 'Olivier Masbou'),
	  );
	}

	/**
	 * @dataProvider extractPostContentProvider
	 */
	public function testExtractPostContent($contentId, $startsWith, $endsWith) {
    $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $content = trim($this->operation->extractPostContent($xpath)->textContent, " \n\r\t\0\x0b\xc2\xa0");

    $this->assertStringStartsWith($startsWith, $content);
    $this->assertStringEndsWith($endsWith, $content);
	}

	public function extractPostContentProvider() {
	  return array(
	    array('boiremanger', 'Ce plat a été fait exprès pour un flacon vénérable de Grande', "L'accord était magnifique."),
	    array('couturejulie', 'et oui fini le rose bonbon et les rayures...', "Pensez à changer vos liens et merci de votre fidélité..."),
	    array('maflo', "ou y'a vraiment plus personne qui attérit sur mon blog????", "Allez à vos méninges et rdv le 18 septembre!"),
	    array('masbou', "Augmentation des surfaces de pommes de terre en Europe", "atteint 355 millions d’euros."),
	  );
	}

	/**
	 * @dataProvider extractCommentsProvider
	 */
	public function testExtractComments($contentId, $expectedCount, $firstCommentData) {
    $html = file_get_contents(__DIR__ . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $comments = $this->operation->extractComments($xpath);

    $this->assertCount($expectedCount, $comments);

    if (!empty($comments)) {
      $firstComment = $comments[0];

      $this->assertEquals($firstCommentData['__comment_id'], $firstComment['__comment_id']);
      $this->assertEquals($firstCommentData['comment_author'], $firstComment['comment_author']);
      $this->assertEquals($firstCommentData['comment_date'], $firstComment['comment_date']);
      $this->assertStringStartsWith($firstCommentData['comment_content'], $firstComment['comment_content']);
      $this->assertEquals($firstCommentData['comment_author_url'], $firstComment['comment_author_url']);
    }
	}

	public function extractCommentsProvider() {
	  return array(
	    array('boiremanger', 3, array(
	      '__comment_id' => '30458956',
	      'comment_author' => 'Celoweb',
	      'comment_date' => '2014-08-23 18:29:06',
	      'comment_content' => 'Je confirme tes dires Eric',
	      'comment_author_url' => '',
	    )),
	    array('couturejulie', 0, array()),
	    array('maflo', 24, array(
	      '__comment_id' => '18732506',
	      'comment_author' => 'opio',
	      'comment_date' => '2010-01-18 16:45:00',
	      'comment_content' => 'bah la 3 après avoir failli',
	      'comment_author_url' => 'http://www.familyandthecity.com',
	    )),
	    array('masbou', 0, array()),
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
	    array('boiremanger', 'http://www.boiremanger.net/archives/2014/08/23/30458956.html', 'Médaillons de langouste, champignons snackés, émulsion aux cèpes et brioche'),
	    array('couturejulie', 'http://lacouturedejulie.canalblog.com/archives/2013/01/02/26050095.html', 'Je déménage...'),
	    array('maflo', 'http://maflo.canalblog.com/archives/2010/09/07/18732506.html', 'personne ne veut de mes places de cinéma à gagner ????'),
	    array('masbou', 'http://www.leblognotesdoliviermasbou.info/archives/2014/07/15/30252678.html', 'Nouvelles fraîches'),
	  );
	}
}

