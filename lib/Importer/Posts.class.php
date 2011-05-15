<?php

class CanalblogImporterImporterPosts extends CanalblogImporterImporterBase
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

    ini_set('memory_limit', '128M');
    $this->arguments['permalinks'] = get_transient('canalblog_permalinks');

    return true;
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	if (!!get_transient('canalblog_have_finished_posts'))
  	{
  		delete_transient('canalblog_have_finished_posts');
  		delete_transient('canalblog_permalinks');
  		delete_transient('canalblog_step_offset');

  		return true;
  	}
 
  	return false;
  }
  
  public function processRemote(WP_Ajax_Response $response)
  {
  	$offset = (int)get_transient('canalblog_step_offset');
  	$new_offset = $offset + 10;
  	$progress = floor(($offset / count($this->arguments['permalinks'])) * 100);
  	$is_finished = false;

  	for ($i = $offset; $i < $new_offset; $i++)
  	{
  		if (!isset($this->arguments['permalinks'][$i]))
  		{
  			$is_finished = true;
  			$progress = 100;
  			$new_offset = count($this->arguments['permalinks']);
  			set_transient('canalblog_have_finished_posts', 1);
  			break;
  		}
  		
  		$permalink = $this->arguments['permalinks'][$i];
      $post = new CanalblogImporterImporterPost($this->getConfiguration());
      $post->setUri($permalink);
      $data = $post->process();
  		
      $message = sprintf(__('<strong>%s</strong> post import: %s', 'canalblog-importer'),
      	$data['post']['title'],
      	$data['post']['status']
      );
      $message .= '<ul>';
      	$message .= '<li>'.sprintf(__('<em>%s comments</em>: %s new, %s skipped, %s overwritten', 'canalblog-importer'),
      		$data['comments']['count'],
      		$data['comments']['new'],
      		$data['comments']['skipped'],
      		$data['comments']['overwritten']
      	).'</li>';
      	$message .= '<li>'.sprintf(__('<em>%s medias</em>: %s new, %s skipped', 'canalblog-importer'),
      		$data['medias']['count'],
      		$data['medias']['new'],
      		$data['medias']['skipped']
      	).'</li>';
      $message .= '</ul>';
      
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
}