<?php

include ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
include ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

class ImportPort extends WP_UnitTestCase {

  protected $importer;
  protected $operation;

  public function setUp() {
    $plugin = WPPluginToolkitPlugin::create('CanalblogImporter', dirname(__FILE__) . '/../bootstrap.php');

    $this->importer = new CanalblogImporterImporter($plugin);
    $this->operation = new CanalblogImporterImporterPost($plugin->getConfiguration());
	  $this->operation->setUri('http://maflo.canalblog.com/archives/2010/09/07/18732506.html');
  }

  /**
   * WordPress will number assets uploads with the same filename
   * So we cleanup assets to avoid failures, due to multiple runs
   */
  public static function setUpBeforeClass() {
    $fs = new WP_Filesystem_Direct(array());

    $fs->delete(wp_upload_dir()['basedir'], true);
    $fs->mkdir(wp_upload_dir()['basedir'], true);
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

	/**
   * @dataProvider extractTitleProvider
	 */
	function testExtractTitle($contentId, $expectation) {
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
	  $dom = $this->operation->getDomDocumentFromHtml($html);
	  $xpath = new DomXpath($dom);

	  $this->assertEquals($expectation, $this->operation->extractTitle($xpath));
	}

  public function extractTitleProvider(){
    return array(
      // h3
      array('maflo', 'personne ne veut de mes places de cinéma à gagner ????'),
      // bookmark title
      array('grisfluo', 'Que rapporter de Lisbonne ?'),
      array('couturejulie', 'Je déménage...'),
      array('masbou', 'Nouvelles fraîches'),
      // next h1/h3 after <a name>
      array('evacuisine', 'Moelleux au citron dans un citron'),
      array('boiremanger', 'Médaillons de langouste, champignons snackés, émulsion aux cèpes et brioche'),
    );
  }

  /**
   * @dataProvider commentsPaginationProvider
   */
  function testExtractCommentsPagination($contentId, $expectedUris) {
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);

    $this->assertEquals($this->operation->extractCommentsPagination($dom), $expectedUris);
  }

  public function commentsPaginationProvider(){
    return array(
      array('maflo', array()),
      array('couturejulie', array()),
      array('masbou', array()),
      array('evacuisine', array('http://www.evacuisine.fr/archives/2009/02/28/12603374-p50-0.html#comments')),
      array('boiremanger', array()),
      array('grisfluo', array()),
    );
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
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
    $dom = $this->operation->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    $this->assertEquals($expected, $this->operation->extractPostdate($xpath));
	}

	public function extractPostDateProvider() {
	  return array(
	    array('boiremanger', '2014-08-23 09:00'),
	    array('couturejulie', '2013-01-02 20:58'),
	    array('maflo', '2010-09-07 13:41'),
	    array('masbou', '2014-07-15 09:54'),
	    array('evacuisine', '2009-02-28 11:10'),
      array('grisfluo', '2015-12-08 16:00'),
	  );
	}

  /**
   * @dataProvider extractPostAuthorNameProvider
   */
	public function testExtractPostAuthorName($contentId, $expected) {
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
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
	    array('evacuisine', 'admin'),
      array('grisfluo', 'gris fluo'),
	  );
	}

	/**
	 * @dataProvider extractPostContentProvider
	 * @group content
	 */
	public function testExtractPostContent($contentId, $startsWith, $endsWith) {
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
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
	    array('evacuisine', "Aujourd'hui, voici une délicieuse recette de moelleux au citron !", "Bonne journée, et à bientôt avec pleins de nouvelles recettes !"),
	    array('grisfluo', "Comment rentrer de Lisbonne sans rapporter quelques boîtes de sardines, maquereaux et autres poissons.", "Le petit moins, lorsque nous y sommes allés, l'accueil a été très moyen, c'était peut être un jour \"sans\"! \n\nI"),
	  );
	}

	/**
	 * @dataProvider extractCommentsProvider
	 */
	public function testExtractComments($contentId, $expectedCount, $firstCommentData) {
    $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
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
	      '__comment_id' => '62701644',
	      'comment_author' => 'Celoweb',
	      'comment_date' => '2014-08-23 18:29:06',
	      'comment_content' => 'Je confirme tes dires Eric',
	      'comment_author_url' => '',
	    )),
	    array('couturejulie', 0, array()),
	    array('maflo', 24, array(
	      '__comment_id' => '36284068',
	      'comment_author' => 'opio',
	      'comment_date' => '2010-01-18 16:45:00',
	      'comment_content' => 'bah la 3 après avoir failli',
	      'comment_author_url' => 'http://www.familyandthecity.com',
	    )),
	    array('masbou', 0, array()),
      array('evacuisine', 50, array(
	      '__comment_id' => '21981351',
	      'comment_author' => "sam's cook",
	      'comment_date' => '2009-02-28 11:14:00',
	      'comment_content' => "y'a pas mieux comme présentation, tu en as de l'imagination! et le citron, j'aime tellement",
	      'comment_author_url' => 'http://missnature.canalblog.com',
	    )),
      array('grisfluo', 2, array(
	      '__comment_id' => '68343351',
	      'comment_author' => "Fée des Brumes",
	      'comment_date' => '2016-01-19 22:46:28',
	      'comment_content' => "Je crois qu'il me reste une boite de mon dernier WE à Lisbonne il y a … deux ans !",
	      'comment_author_url' => 'http://www.feedesbrumes.com/',
	    )),
	  );
	}


	/**
   * @dataProvider postContentProvider
   * @group content
   */
	function testSavePost($contentId, $uri, $title, $commentsCount) {
	  $html = file_get_contents(dirname(__FILE__) . '/fixtures/post-'. $contentId .'.html');
	  $dom = $this->operation->getDomDocumentFromHtml($html);

	  $this->operation->setUri($uri);
	  $result = $this->operation->savePost($dom, $html);
	  $comments = $this->operation->savePaginatedComments($dom, $html);

    $this->assertEquals($result['status'], 'imported');
	  $this->assertInternalType('integer', $result['id']);
	  $this->assertEquals('imported', $result['status']);
	  $this->assertEquals($title, $result['title']);
	  $this->assertEquals($comments['count'], $commentsCount);
	}

	public function postContentProvider() {
	  return array(
	    array('boiremanger', 'http://www.boiremanger.net/archives/2014/08/23/30458956.html', 'Médaillons de langouste, champignons snackés, émulsion aux cèpes et brioche', 3),
	    array('couturejulie', 'http://lacouturedejulie.canalblog.com/archives/2013/01/02/26050095.html', 'Je déménage...', 0),
	    array('maflo', 'http://maflo.canalblog.com/archives/2010/09/07/18732506.html', 'personne ne veut de mes places de cinéma à gagner ????', 24),
	    array('masbou', 'http://www.leblognotesdoliviermasbou.info/archives/2014/07/15/30252678.html', 'Nouvelles fraîches', 0),
	    // array('evacuisine', 'http://www.evacuisine.fr/archives/2009/02/28/12603374.html', 'Moelleux au citron dans un citron', 69),
      array('grisfluo', 'http://grisfluo.canalblog.com/archives/2015/12/08/33097920.html', 'Que rapporter de Lisbonne ?', 2),
	  );
	}

  /**
   * @group content
   */
	public function testExtractMediaUris(){
	  $html = file_get_contents(dirname(__FILE__) . '/fixtures/media-suite.html');
	  $uris = $this->operation->extractMediaUris($html);

	  $this->assertCount(5, $uris);
	  $this->assertContains([
      'uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
      'original_uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
      'size' => 'full',
    ], $uris);
	  $this->assertContains([
      'uri' => 'http://p1.storage.canalblog.com/12/96/1014282/94464164.pdf',
      'original_uri' => 'http://p1.storage.canalblog.com/12/96/1014282/94464164.pdf',
      'size' => 'full',
    ], $uris);
	  $this->assertContains([
      'uri' => 'http://static.canalblog.com/storagev1/concoursattache.canalblog.com/docs/introduction.pdf',
      'original_uri' => 'http://static.canalblog.com/storagev1/concoursattache.canalblog.com/docs/introduction.pdf',
      'size' => 'full',
    ], $uris);
	  $this->assertContains([
      'uri' => 'http://frances1.canalblog.com/docs/Caractere.pdf',
      'original_uri' => 'http://frances1.canalblog.com/docs/Caractere.pdf',
      'size' => 'full',
    ], $uris);
	  $this->assertContains([
      'uri' => 'http://postaisportugal.canalblog.com/images/t-Fond_d_ecran9.jpg',
      'original_uri' => 'http://postaisportugal.canalblog.com/images/Fond_d_ecran9.jpg',
      'size' => 'thumbnail',
    ], $uris);
	}
	/**
   * @dataProvider importAttachmentsProvider
   * @depends testSavePost
   * @group content
   */
	public function testImportAttachments($attachment, $statsExpectation) {
    extract($attachment);
	  $this->operation->requireWordPressImporter($this->operation->getConfiguration());

	  $post = get_post(3, 'ARRAY_A');

    $stats = array('skipped' => 0, 'new' => 0);
    $wpImport = new WP_Import();
    $wpImport->fetch_attachments = true;

    $attachments = $this->operation->importAttachments($wpImport, $post, array($attachment), $stats);

    // it belongs to saveMedias() but it's easier to test here
    $wpImport->url_remap = $this->operation->updateAttachmentsRemap($attachments);
    $this->assertArrayHasKey($uri, $wpImport->url_remap);

    $this->assertEquals($statsExpectation, $stats);
    $this->assertArraySubset($attachment, $attachments[$uri]);
    $this->assertIsInt($attachments[$uri]['id']);
	}

	public function importAttachmentsProvider() {
	  return [
      [
        'attachment' => [
          // 'id' => 9
          'uri' => 'http://storage.canalblog.com/09/65/501700/34561690_p.jpg',
          'original_uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
          'size' => 'medium',
        ],
        'statsExpectation' => ['new' => 1, 'skipped' => 0],
      ],
      [
        'attachment' => [
          // 'id' => 10
          'uri' => 'http://postaisportugal.canalblog.com/images/t-Fond_d_ecran9.jpg',
          'original_uri' => 'http://postaisportugal.canalblog.com/images/Fond_d_ecran9.jpg',
          'size' => 'thumbnail',
        ],
        'statsExpectation' => ['new' => 1, 'skipped' => 0],
      ],
      [
        'attachment' => [
          // 'id' => 9
          'uri' => 'http://storage.canalblog.com/09/65/501700/34561690_q.jpg',
          'original_uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
          'size' => 'thumbnail',
        ],
        'statsExpectation' => ['new' => 0, 'skipped' => 1],
      ],
      [
        'attachment' => [
          // 'id' => 11
          'uri' => 'http://p7.storage.canalblog.com/79/42/1295810/98533741.jpg',
          'original_uri' => 'http://p7.storage.canalblog.com/79/42/1295810/98533741.jpg',
          'size' => 'full',
        ],
        'statsExpectation' => ['new' => 1, 'skipped' => 0],
      ],
      [
        'attachment' => [
          // 'id' => 12
          'uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
          'original_uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
          'size' => 'full',
        ],
        'statsExpectation' => ['new' => 1, 'skipped' => 0],
      ],
	  ];
	}

  /**
   * @depends testSavePost
   */
	public function testUpdateAttachmentsRemapWithEmptydata() {
    $result = $this->operation->updateAttachmentsRemap(array());

    $this->assertEmpty($result);
	}

  /**
   * @dataProvider updateAttachmentsRemapProvider
   * @depends testSavePost
   * @group content
   */
	public function testUpdateAttachmentsRemap($attachments, $expectations) {
    $wpImport = new WP_Import();
    $wpImport->fetch_attachments = true;

    $wpImport->url_remap = $this->operation->updateAttachmentsRemap($attachments);

    $this->assertEquals($expectations, $wpImport->url_remap);
	}

	public function updateAttachmentsRemapProvider() {
    $yearMonthNow = strftime('%Y/%m');

	  return array(
      [
        'attachments' => [
          'http://storage.canalblog.com/09/65/501700/34561690_p.jpg' => [
            'uri' => 'http://storage.canalblog.com/09/65/501700/34561690_p.jpg',
            'original_uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
            'size' => 'medium',
            'id' => 9,
          ],
          'http://postaisportugal.canalblog.com/images/t-Fond_d_ecran9.jpg' => [
            'uri' => 'http://postaisportugal.canalblog.com/images/t-Fond_d_ecran9.jpg',
            'original_uri' => 'http://postaisportugal.canalblog.com/images/Fond_d_ecran9.jpg',
            'size' => 'thumbnail',
            'id' => 10,
          ],
          'http://storage.canalblog.com/09/65/501700/34561690_q.jpg' => [
            'uri' => 'http://storage.canalblog.com/09/65/501700/34561690_q.jpg',
            'original_uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
            'size' => 'thumbnail',
            'id' => 9,
          ],
          'http://storage.canalblog.com/09/65/501700/34561690.jpg' => [
            'uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
            'original_uri' => 'http://storage.canalblog.com/09/65/501700/34561690.jpg',
            'size' => 'full',
            'id' => 9,
          ],
          'http://p7.storage.canalblog.com/79/42/1295810/98533741.to_resize_150x3000.jpg' => [
            'uri' => 'http://p7.storage.canalblog.com/79/42/1295810/98533741.to_resize_150x3000.jpg',
            'original_uri' => 'http://p7.storage.canalblog.com/79/42/1295810/98533741.jpg',
            'size' => 'full',
            'id' => 11,
          ],
          'http://storage.canalblog.com/65/79/829482/64555901.pdf' => [
            'uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
            'original_uri' => 'http://storage.canalblog.com/65/79/829482/64555901.pdf',
            'size' => 'full',
            'id' => 12,
          ],
        ],
        'expectation' => [
          'http://storage.canalblog.com/09/65/501700/34561690_p.jpg' => 'http://example.org/wp-content/uploads/'. $yearMonthNow .'/34561690-300x200.jpg',
          'http://postaisportugal.canalblog.com/images/t-Fond_d_ecran9.jpg' => 'http://example.org/wp-content/uploads/'. $yearMonthNow .'/Fond_d_ecran9-150x150.jpg',
          'http://storage.canalblog.com/09/65/501700/34561690_q.jpg' => 'http://example.org/wp-content/uploads/'. $yearMonthNow .'/34561690-150x150.jpg',
          'http://storage.canalblog.com/09/65/501700/34561690.jpg' => 'http://example.org/wp-content/uploads/'. $yearMonthNow .'/34561690.jpg',
          'http://p7.storage.canalblog.com/79/42/1295810/98533741.to_resize_150x3000.jpg' => 'http://example.org/wp-content/uploads/'. $yearMonthNow .'/98533741.jpg',
          'http://storage.canalblog.com/65/79/829482/64555901.pdf' => NULL,
        ]
      ]
    );
	}

  /**
   * @dataProvider savePostMediasProvider
   * @depends testSavePost
   */
	public function testSavePostMedias($post_id, $expectedCount) {
    $result = $this->operation->saveMedias(get_post($post_id, ARRAY_A));
    $this->assertEquals($expectedCount, $result['new']);
    $this->assertEquals(0, $result['overwritten']);
    $this->assertEquals(0, $result['skipped']);
    $this->assertEquals($expectedCount, $result['count']);
	}

	public function savePostMediasProvider() {
	  return array(
	    array(4, 2),
	    array(5, 0),
	    array(6, 0),
	  );
	}
}
