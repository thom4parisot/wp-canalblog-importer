<?php
/**
 * Basic Exception
 *
 * @author oncletom
 * @package canalblog-importer
 * @subpackage lib
 */
class CanalblogImporterException extends Exception
{
  /**
   * Override the display output of the exception for WordPress
   *
   * @author oncletom
   * @see Exception::__toString()
   */
  public function __toString()
  {
    wp_die($this->getMessage().'<br /><pre>'.$this->getTraceAsString().'</pre>', 'Canalblog Importer exception');
  }

  /**
   * Rethrows the current exception
   *
   * Yes we do
   *
   * @author oncletom
   */
  public function rethrow()
  {
    throw $this;
  }
}
