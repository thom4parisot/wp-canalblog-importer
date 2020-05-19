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

    $this->arguments['months'] = $this->getMonths();

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	if (!!get_transient('canalblog_have_finished_archives'))
  	{
  		delete_transient('canalblog_have_finished_archives');
  		delete_transient('canalblog_months');
  		delete_transient('canalblog_step_offset');

  		return true;
  	}

  	return false;
  }

  public function processRemote(WP_Ajax_Response $response)
  {
  	$months = $this->arguments['months'];
  	$permalinks = get_transient('canalblog_permalinks');

  	$this->setupProcess(array(
  		'offset' => get_transient('canalblog_step_offset'),
  		'limit' => 1,
  		'total' => count($months),
  	));

  	if (!is_array($permalinks))
  	{
  		$permalinks = array();
  	}

  	for ($i = $this->offset; $i < $this->new_offset; $i++)
  	{
  		if (!isset($months[$i]))
  		{
  			$this->setProcessFinished('canalblog_have_finished_archives');
  			break;
  		}

	    $archives_index = $months[$i];
	    $month_permalinks = $this->getMonthPermalinks($archives_index['year'], $archives_index['month']);
	    $permalinks = array_merge($permalinks, $month_permalinks);
	    $permalinks = array_unique($permalinks);
	    set_transient('canalblog_permalinks', $permalinks);

	    $message = sprintf(__('<strong>%s/%s</strong>: found %s posts.', 'canalblog-importer'),
	    	$archives_index['year'],
	    	$archives_index['month'],
	    	count($month_permalinks)
	    );

  		$response->add(array(
  			'data' => $message,
  		));
  	}

  	$this->processRemoteShutdown($response);
  }

  /**
   * Retrieves all permalinks within a month archive
   * @param unknown_type $year
   * @param unknown_type $month
   * @return unknown_type
   */
  public function getMonthPermalinks($year, $month)
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
  		foreach ($xpath->query("//div[@id='content']//a[@rel='bookmark' or .='#']") as $node)
	    {
	      $permalinks[] = $node->getAttribute('href');
	    }

	    /*
	     * Going to next page?
	     */
	    $next = $xpath->query("//div[@id='content']//div[last()]/a[contains(@title,'suivant') or contains(.,'suivant')]");
	    if ($next instanceof DOMNodeList && $next->length > 0)
	    {
	    	$offset += 10;
	    }
	    else
	    {
	    	$offset = -1;
	    }
  	}

    /*
     * Collecting other pages
     * Skipping first link and next page
     */
    foreach ($xpath->query("//div[@id='content']//div[last()]/a[position()>1 and position()<last()]") as $node)
    {
      if (preg_match('#/archives/\d{4}/\d{2}/p\d+-\d+\.html#U', $node->getAttribute('href')))
      {
        foreach ($this->getRemoteXpath($node->getAttribute('href'), "//div[@id='content']//a[@rel='bookmark']") as $node)
        {
          $permalinks[] = $node->getAttribute('href');
        }
      }
    }

    return array_unique($permalinks);
  }

  /**
   * Retrieves categories from Canalblog
   *
   * @author oncletom
   * @return unknown_type
   */
  public function getMonths()
  {
  	if ($months = get_transient('canalblog_months'))
  	{
  		return $months;
  	}

    foreach ($this->getRemoteXpath(get_option('canalblog_importer_blog_uri').'/archives/', "//div[@id='content']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/(\d{4})/(\d{2})/index.html$#iU', $node->getAttribute('href'), $matches))
      {
        $months[] = array('year' => $matches[1], 'month' => $matches[2]);
      }
    }

    unset($dom, $http);
    set_transient('canalblog_months', $months);

    return $months;
  }
}
