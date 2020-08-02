<?php

/**
 * Base class for importing process
 *
 * @abstract
 * @author oncletom
 * @since 1.0
 */
abstract class CanalblogImporterImporterBase
{
  protected $arguments = array();
  protected $configuration;
  protected static $http_retry_count =  0;
  public static $http_retry_delay =     2;    //in seconds, for sleep
  public static $http_max_retry_count = 5;
  protected static $wordpress_importer_locations = array();

  /**
   * Constructor
   *
   * Basically stores configuration
   *
   * @author oncletom
   * @param CanalblogImporterConfiguration $configuration
   */
  public function __construct(CanalblogImporterConfiguration $configuration)
  {
    $this->configuration = $configuration;
  }

  /**
   * Returns the list of internal arguments
   *
   * @author oncletom
   * @return Array
   */
  public function getArguments()
  {
    return $this->arguments;
  }

  /**
   * Returns the internal configuration
   *
   * @author oncletom
   * @return CanalblogImporterConfiguration
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  public function mapResponseUrl($response) {
    return $response->url;
  }

  /**
   * Transform an HTML string into DomDocument object
   *
   * @version 1.0
   * @since 1.0.3
   * @param string $html
   *
   * @return DomDocument
   */
  public function getDomDocumentFromHtml(&$html, $stripJavaScript = true)
  {
    //removing all scripts (we don't want them)
    if (!!$stripJavaScript)
    {
      $html = preg_replace('#<script[^>]*>.*<\/script>#siU', '', trim($html));
    }

    /*
     * Fixing UTF8 encoding on existing full document
     * @props ricola
     */
    if (preg_match('/<head/iU', $html))
    {
      $html = preg_replace('/(<head[^>]*>)/siU', '\\1<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />', $html);
    }
    /*
     * Fixing UTF8 on HTML fragment
     */
    else
    {
      $html = sprintf('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>%s</body></html>', $html);
    }

    $dom = new DomDocument();
    $dom->preserveWhitespace = false;
    @$dom->loadHTML($html);

    return $dom;
  }

  /**
   * Retrieves a page content based on its URI
   *
   * @author oncletom
   * @since 1.0.3
   * @param string $uri
   * @throws CanalblogImporterException in case of error during request or something else
   *
   * @return string HTML content of $uri
   */
  public function getRemoteHtml($uri)
  {
    $http = new Wp_HTTP();

    try{
      $result = $http->get($uri, array(
        'redirection' =>  5,
        'timeout' =>      5,
        'user-agent' =>   'WordPress/'.get_bloginfo('version')
      ));

      if (is_wp_error($result))
      {
        throw new CanalblogImporterException(sprintf(__("HTTP request returned an error: %s [%s]", 'canalblog-importer'), $result->get_error_message(), $uri));
      }
      elseif (!is_array($result))
      {
        throw new CanalblogImporterException(sprintf(__("HTTP request did not returned an expected result [%s]", 'canalblog-importer'), $uri));
      }
      elseif (!isset($result['response']['code']) || preg_match('#^[4,5]\d{2}#sU', (int)$result['response']['code']))
      {
        throw new CanalblogImporterException(sprintf(__("Tried to request an unavailable page [%s] (%d, %s)<br><strong>Redirects</strong>: <code>%s</code><br><strong>Content</strong>: <details>%s</details>", 'canalblog-importer'),
          $uri,
          $result['response']['code'],
          $result['response']['message'],
          join(', ', array_map(array($this, 'mapResponseUrl'), $result['http_response']->get_response_object()->history)),
          $result['body']
        ));
      }
      elseif (!isset($result['body']) || empty($result['body']))
      {
        throw new CanalblogImporterException(sprintf(__("Remote document is empty [%s]", 'canalblog-importer'), $uri));
      }
      else
      {
        unset($http);
        self::$http_retry_count = 0;
        self::$http_retry_delay = 2;
        return $result['body'];
      }
    }
    catch (CanalblogImporterException $e)
    {
      /*
       * Retry the download another time, with a small delay (1s)
       */
      if (++self::$http_retry_count <= self::$http_max_retry_count)
      {
        sleep(self::$http_retry_delay++);
        return $this->getRemoteHtml($uri);
      }
      else
      {
        throw new CanalblogImporterException($e->getMessage());
      }
    }
    catch (Exception $e)
    {
      throw new CanalblogImporterException(sprintf(__("An error occured during HTTP request: %s. [%s]", 'canalblog-importer'), $e->getMessage(), $uri));
    }

  }

  /**
   * Returns a remote page as Dom Document
   *
   * @author oncletom
   * @param String $uri
   * @return DomDocument
   */
  public function getRemoteDomDocument($uri, &$html = '')
  {
    $html = $this->getRemoteHtml($uri);
    $dom = $this->getDomDocumentFromHtml($html);

    return $dom;
  }

  /**
   * Returns a result of XPath query on a remote page
   *
   * @author oncletom
   * @param String $uri
   * @param String $xpath_query
   * @return DomNodeList
   */
  public function getRemoteXpath($uri, $xpath_query, &$html = '')
  {
    $dom = $this->getRemoteDomDocument($uri, $html);
    $xpath = new DOMXPath($dom);

    $result = $xpath->query($xpath_query);
    unset($http, $dom, $xpath);
    return $result;
  }

  /*
   * WordPress Importer check
   */

  /**
   * Returns the WordPress importer location
   *
   * @since 1.1.4
   * @param CanalblogImporterConfiguration $configuration
   * @return string|boolean
   */
  public static function getWordPressImporterLocation(CanalblogImporterConfiguration $configuration)
  {
    foreach ($configuration->getWordPressImporterLocations() as $location)
    {
      if (file_exists($location))
      {
        return $location;
      }
    }

    return null;
  }

  /**
   * Checks if the WordPress importer is available
   *
   * @since 1.1.4
   * @param CanalblogImporterConfiguration $configuration
   * @return boolean
   */
  public static function isWordPressImporterInstalled(CanalblogImporterConfiguration $configuration)
  {
    return file_exists(self::getWordPressImporterLocation($configuration));
  }

  /**
   * Requires the available WordPress Importer
   *
   * @since 1.1.4
   * @throws CanalblogImporterException
   */
  public static function requireWordPressImporter(CanalblogImporterConfiguration $configuration)
  {
    if (self::isWordPressImporterInstalled($configuration))
    {
    	if (!defined('WP_LOAD_IMPORTERS'))
    	{
    		define('WP_LOAD_IMPORTERS', true);
    	}

      require_once self::getWordPressImporterLocation($configuration);
    }
    else
    {
      throw new CanalblogImporterException("WordPress Importer could not be found.");
    }
  }

  /**
   * Shutdown of the remote process
   *
   * Basically, cleanup and last reponse element
   * @param WP_Ajax_Response $response
   */
  protected function processRemoteShutdown(WP_Ajax_Response $response)
  {
  	set_transient('canalblog_step_offset', $this->new_offset);
  	$response->add(array(
  		'what' => 'operation',
  		'supplemental' => array(
  			'finished' => (int)$this->is_finished,
  			'progress' => $this->progress,
  		)
  	));
  }

  /**
   * Sets everything related to the ending of the process
   *
   * @protected
   * @param string $transient_id
   * @return boolean transient storage success
   */
  protected function setProcessFinished($transient_id)
  {
  	$this->is_finished = true;
  	$this->progress = 100;
  	$this->new_offset = $this->total;

  	return set_transient($transient_id, 1);
  }

  /**
   * Setups a process phase
   *
   * @protected
   * @param array $options
   */
  protected function setupProcess(array $options)
  {
  	$this->is_finished = false;
  	$this->limit = 25;
  	$this->new_offset = 0;
  	$this->offset = 0;
  	$this->progress = 0;
  	$this->total = 0;

  	if (isset ($options['offset']))
  	{
  		$this->offset = intval($options['offset']);
  	}

  	if (isset($options['limit']))
  	{
  		$this->limit = intval($options['limit']);
  		$this->new_offset = $this->offset + $this->limit;
  	}

  	if (isset ($options['total']))
  	{
  		$this->total = intval($options['total']);
  	}

  	if ($this->total > 0)
  	{
  		$this->progress = floor(($this->offset / $this->total) * 100);
  	}
  }

  /**
   * Checks if the import step is ready to run
   *
   * @author oncletom
   * @abstract
   * @return Boolean true if it's ok to process
   */
  abstract public function dispatch();

  /**
   * Process the import step
   *
   * @author oncletom
   * @abstract
   * @return Boolean true if it's ok to proceed to next step
   */
  abstract public function process();
}
