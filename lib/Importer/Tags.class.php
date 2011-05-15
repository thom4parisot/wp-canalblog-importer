<?php
/**
 * Import tags from a remote blog
 *
 * @author oncletom
 * @since 1.0
 */
class CanalblogImporterImporterTags extends CanalblogImporterImporterBase
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

    $this->arguments['tags'] = $this->getTags();

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	if (!!get_transient('canablog_have_finished_tags'))
  	{
  		delete_transient('canablog_have_finished_tags');
  		delete_transient('canalblog_tags');
  		delete_transient('canalblog_tags_offset');
  		
  		return true;
  	}
  	
  	return false;
  }
  
  public function processRemote(WP_Ajax_Response $response)
  {
  	$tags = $this->arguments['tags'];
  	$offset = (int)get_transient('canalblog_tags_offset');
  	$new_offset = $offset + 50;
  	$progress = floor(($offset / count($tags)) * 100);
  	$is_finished = false;
  	
  	for ($i = $offset; $i < $new_offset; $i++)
  	{
  		if (!isset($tags[$i]))
  		{
  			$is_finished = true;
  			$progress = 100;
  			$new_offset = count($tags);
  			set_transient('canablog_have_finished_tags', 1);
  			break;
  		}
  		
  		$tag = $tags[$i];
  		if (tag_exists($tag))
  		{
  			$message = sprintf(__('<strong>%s</strong> already exists. Skipped.', 'canalblog-importer'), $tag);
  		}
  		elseif (wp_create_tag($tag))
  		{
  			$message = sprintf(__('<strong>%s</strong> tag created.', 'canalblog-importer'), $tag);
  		}
  		else 
  		{
  			$message = sprintf(__('<strong>%s</strong> tag creation failed.', 'canalblog-importer'), $tag);
  		}
  		
  		$response->add(array(
  			'data' => $message,
  		));
  	}
  	
  	set_transient('canalblog_tags_offset', $new_offset);
  	$response->add(array(
  		'what' => 'operation',
  		'supplemental' => array(
  			'finished' => (int)$is_finished,
  			'progress' => $progress,
  		)
  	));
  }

  /**
   * Retrieves the tags from remote blog
   *
   * @author oncletom
   * @protected
   * @return Array
   */
  protected function getTags()
  {
  	if ($tags = get_transient('canalblog_tags'))
  	{
  		return $tags;
  	}

    $dom = $this->getRemoteDomDocument(get_option('canalblog_importer_blog_uri').'/archives/');
    $xpath = new DOMXPath($dom);
    $tags = array();

    foreach ($xpath->query("//div[@class='blogbody']//ul[@class='taglist']//a[@rel='tag']") as $node)
    {
      $tags[] = $node->nodeValue;
    }

    unset($dom, $xpath);
    set_transient('canalblog_tags', $tags);

    return $tags;
  }
}