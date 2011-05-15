<?php

class CanalblogImporterImporterArchives extends CanalblogImporterImporterBase
{
  /**
   * @see lib/Importer/CanalblogImporterImporterBase#dispatch()
   */
  public function dispatch()
  {
    if (!get_option('canalblog_importer_blog_uri'))
    {
      return false;
    }

    $this->arguments['page'] =   get_option('canalblog_importer_archives_current_index', 0);
    $this->arguments['months'] = $this->getMonths();

    set_time_limit(60);
    ini_set('memory_limit', '128M');

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
    /*
     * No index defined? We can go the next step
     */
    if (empty($this->arguments['months']) || !isset($this->arguments['months'][$this->arguments['page']]))
    {
      return true;
    }

    $archives_index = $this->arguments['months'][$this->arguments['page']];
    $permalinks = $this->getMonthPermalinks($archives_index['year'], $archives_index['month']);

    foreach ($permalinks as $permalink)
    {
      /*
       * Importing post content
       */
      $post = new CanalblogImporterImporterPost($this->getConfiguration());
      $post->setUri($permalink);
      $post->process();
    }

    update_option('canalblog_importer_archives_current_index', $this->arguments['page'] + 1);
    $url = '?import=canalblog&step='.get_option('canalblog_importer_step').'&process-import=1&_wpnonce='.wp_create_nonce('import-canalblog');
    echo '<script type="text/javascript">setTimeout(function(){window.location.href = "'.$url.'";}, 1000);</script>';
  }

  /**
   * Retrieves all permalinks within a month archive
   * 
   * @param string $year
   * @param string $month
   * @return array
   */
  protected function getMonthPermalinks($year, $month)
  {
  	$offset = 0;
  	$permalinks = array();

  	/*
  	 * Browsing page per page
  	 */
  	while ($offset !== -1)
  	{
  		$uri_suffix = sprintf('%s/%s/p%s-0.html', $year, $month, $offset);
  		$dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/'.$uri_suffix);
  		$xpath = new DOMXPath($dom);
 
  		/*
  		 * Collecting archive permalinks
  		 */
  		foreach ($xpath->query("//div[@id='content']//a[@rel='bookmark']") as $node)
	    {
	      $permalinks[] = $node->getAttribute('href');
	    }

	    /*
	     * Going to next page?
	     */
	    $next = $xpath->query("//div[@id='content']//div[last()]/a[contains(@title,'suivant')]");
	    if ($next instanceof DOMNodeList && $next->length > 0)
	    {
	    	$offset += 10;
	    }
	    else 
	    {
	    	$offset = -1;
	    }
  	}

    return $permalinks;
  }

  /**
   * Retrieves categories from Canalblog
   *
   * @author oncletom
   * @return unknown_type
   */
  protected function getMonths()
  {
    foreach ($this->getRemoteXpath(get_option('canalblog_importer_blog_uri').'/archives/', "//div[@id='content']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/(\d{4})/(\d{2})/index.html$#iU', $node->getAttribute('href'), $matches))
      {
        $months[] = array('year' => $matches[1], 'month' => $matches[2]);
      }
    }

    unset($dom, $http);
    return $months;
  }
}