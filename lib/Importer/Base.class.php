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
   * Returns a remote page as Dom Document
   *
   * @author oncletom
   * @param String $uri
   * @return DomDocument
   */
  public function getRemoteDomDocument($uri)
  {
    $http = new Wp_HTTP();
    $result = $http->get($uri);

    $dom = new DomDocument();
    $dom->preserveWhitespace = false;
    @$dom->loadHTML($result['body']);

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
  public function getRemoteXpath($uri, $xpath_query)
  {
    $dom = $this->getRemoteDomDocument($uri);
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