<?php

class CanalblogImporterImporterCategories extends CanalblogImporterImporterBase
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

    $this->arguments['categories'] = $this->getCategories();

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	if (!!get_transient('canalblog_have_finished_categories'))
  	{
  		delete_transient('canalblog_have_finished_categories');
  		delete_transient('canalblog_categories');
  		delete_transient('canalblog_step_offset');
  		
  		return true;
  	}
  	
  	return false;
  }
  
  public function processRemote(WP_Ajax_Response $response)
  { 
  	$categories = $this->arguments['categories'];
  	$offset = (int)get_transient('canalblog_step_offset');
  	$new_offset = $offset + 50;
  	$progress = floor(($offset / count($categories)) * 100);
  	$is_finished = false;
  	
  	for ($i = $offset; $i < $new_offset; $i++)
  	{
  		if (!isset($categories[$i]))
  		{
  			$is_finished = true;
  			$progress = 100;
  			$new_offset = count($categories);
  			set_transient('canalblog_have_finished_categories', 1);
  			break;
  		}
  		
  		$category = $categories[$i];
  		if (category_exists($category))
  		{
  			$message = sprintf(__('<strong>%s</strong> already exists. Skipped.', 'canalblog-importer'), $category);
  		}
  		elseif (wp_create_category($category))
  		{
  			$message = sprintf(__('<strong>%s</strong> category created.', 'canalblog-importer'), $category);
  		}
  		else 
  		{
  			$message = sprintf(__('<strong>%s</strong> category creation failed.', 'canalblog-importer'), $category);
  		}
  		
  		$response->add(array(
  			'data' => $message,
  		));
  	}
  	
  	set_transient('canalblog_step_offset', $new_offset);
  	$response->add(array(
  		'what' => 'operation',
  		'supplemental' => array(
  			'finished' => (int)$is_finished,
  			'progress' => $progress,
  		)
  	));
  }

  /**
   * Retrieves categories from Canalblog
   *
   * @author oncletom
   * @return unknown_type
   */
  protected function getCategories()
  {
  	if ($categories = get_transient('canalblog_categories'))
  	{
  		return $categories;
  	}

    $dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/');
    $xpath = new DOMXPath($dom);
    $categories = array();

    foreach ($xpath->query("//div[@class='blogbody']//p/a[@href]") as $node)
    {
      if (preg_match('#archives/[^\/]+/index.html$#iU', $node->getAttribute('href')))
      {
        $categories[] = $node->textContent;
      }
    }

    unset($dom, $xpath);
    set_transient('canalblog_categories', $categories);

    return $categories;
  }
}