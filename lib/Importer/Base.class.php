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
  public static $http_retry_delay =     500000;    //milliseconds for usleep usage (0.5s)
  public static $http_max_retry_count = 5;

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

  /**
   * Transform an HTML string into DomDocument object
   *
   * @version 1.0
   * @since 1.0.3
   * @param string $html
   *
   * @return DomDocument
   */
  public function getDomDocumentFromHtml(&$html)
  {
    //removing all scripts (we don't want them)
    $html = preg_replace('#<script.+>.+<\/script>#siU', '', trim($html));

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
      $result = $http->get($uri);

      if (is_wp_error($result))
      {
        throw new CanalblogImporterException(sprintf(__("HTTP request returned an error: %s [%s]", 'canalblog-importer'), $result->get_error_message(), $uri));
      }
      elseif (!is_array($result))
      {
        throw new CanalblogImporterException(sprintf(__("HTTP request did not returned an expected result [%s]", 'canalblog-importer'), $uri));
      }
      elseif (!isset($result['response']['code']) || (int)$result['response']['code'] !== 200)
      {
        throw new CanalblogImporterException(sprintf(__("Tried to request an unavailable page [%s]", 'canalblog-importer'), $uri));
      }
      elseif (!isset($result['body']) || empty($result['body']))
      {
        throw new CanalblogImporterException(sprintf(__("Remote document is empty [%s]", 'canalblog-importer'), $uri));
      }
      else
      {
        unset($http);
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
        usleep(self::$http_retry_delay);
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