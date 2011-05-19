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

    $this->arguments['permalinks'] = get_transient('canalblog_permalinks');
    ini_set('memory_limit', '128M');

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
  	
  	$this->setupProcess(array(
  		'offset' => get_transient('canalblog_step_offset'),
  		'limit' => 10,
  		'total' => count($this->arguments['permalinks']),
  	));

  	for ($i = $this->offset; $i < $this->new_offset; $i++)
  	{
  		if (!isset($this->arguments['permalinks'][$i]))
  		{
  			$this->setProcessFinished('canalblog_have_finished_posts');
  			break;
  		}

      $post = new CanalblogImporterImporterPost($this->getConfiguration());
      $post->setUri($this->arguments['permalinks'][$i]);
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
  	
  	$this->processRemoteShutdown($response);
  }
}