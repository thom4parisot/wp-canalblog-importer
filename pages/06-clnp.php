<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <p><strong><?php _e('Import Steps', 'canalblog-importer') ?></strong></p>
  <ol>
    <li><?php _e('Configuration', 'canalblog-importer') ?></li>
    <li><?php _e('Tags', 'canalblog-importer') ?></li>
    <li><?php _e('Categories', 'canalblog-importer') ?></li>
    <li><?php _e('Archives', 'canalblog-importer') ?></li>
    <li><?php _e('Posts, comments and media', 'canalblog-importer') ?></li>
    <li><strong><?php _e('Cleanup', 'canalblog-importer') ?></strong></li>
  </ol>

  <h3><?php _e('Cleanup', 'canalblog-importer') ?></h3>

  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php _e('This operation will basically fix all links on your blog and cleanup temporary data stored for all previous steps. It will be quick, promise!', 'canalblog-importer') ?></p>

    <?php include dirname(__FILE__).'/form-submit.php' ?>
  </form>
  
  <?php include dirname(__FILE__).'/ajax-results.php' ?>
</div>