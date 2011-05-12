<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <p><strong><?php _e('Import Steps', 'canalblog-importer') ?></strong></p>
  <ol>
    <li><?php _e('Configuration', 'canalblog-importer') ?></li>
    <li><strong><?php _e('Tags', 'canalblog-importer') ?></strong></li>
    <li><?php _e('Categories', 'canalblog-importer') ?></li>
    <li><?php _e('Archives', 'canalblog-importer') ?></li>
    <li><?php _e('Cleanup', 'canalblog-importer') ?></li>
  </ol>

  <h3><?php _e('Tags', 'canalblog-importer') ?></h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php printf(__('About to import <strong>%s tags</strong>.', 'canalblog-importer'), count($tags)) ?></p>

    <p class="submit">
      <input type="button" class="button-primary start-remote-operation" value="<?php echo esc_attr__('Import Tags', 'canalblog-importer') ?>" />
      <input type="submit" class="button-primary next-operation hidden" value="<?php echo esc_attr__('Next Step →', 'canalblog-importer') ?>" />
      <a href="<?php echo wp_nonce_url('import.php?import=canalblog&cancel=1', 'import-canalblog-cancel') ?>" class="button"><?php echo esc_attr__('Cancel', 'canalblog-importer') ?></a>
    </p>
  </form>
  
  <div id="ajax-results" class="hidden hide-if-no-js updated">
  	
  	<p class="worker-container">
  		<img src="<?php echo get_admin_url() ?>/images/wpspin_light.gif" alt="<?php _e('Loading') ?>" />
  		<?php _e('Operation in progress...', 'canalblog-importer') ?>
  		<span id="import-progress-value">0</span>%	
  	</p>

		<ul id="ajax-responses"></ul>
  </div>
</div>