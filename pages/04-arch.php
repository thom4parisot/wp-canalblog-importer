<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <p><strong><?php _e('Import Steps', 'canalblog-importer') ?></strong></p>
  <ol>
    <li><?php _e('Configuration', 'canalblog-importer') ?></li>
    <li><?php _e('Tags', 'canalblog-importer') ?></li>
    <li><?php _e('Categories', 'canalblog-importer') ?></li>
    <li><strong><?php _e('Archives', 'canalblog-importer') ?></strong></li>
    <li><?php _e('Posts, comments and media', 'canalblog-importer') ?></li>
    <li><?php _e('Cleanup', 'canalblog-importer') ?></li>
  </ol>

  <h3><?php _e('Archives', 'canalblog-importer') ?></h3>
  <p><?php _e('This step scans the presence of all the public posts for your whole history. Their content will be imported during the next step.', 'canalblog-importer') ?></p>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php printf(__('<strong>%s months of posts</strong> to scan before import.', 'canalblog-importer'), count($months), $months[$page]['month'], $months[$page]['year'], count($months) - $page) ?></p>

    <?php include dirname(__FILE__).'/form-submit.php' ?>
  </form>
  
  <?php include dirname(__FILE__).'/ajax-results.php' ?>
</div>