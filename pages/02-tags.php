<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer') ?></h2>

  <p><strong><?php _e('Import Steps') ?></strong></p>
  <ol>
    <li>Configuration</li>
    <li><strong>Tags</strong></li>
    <li>Categories</li>
    <li>Archives</li>
    <li>Cleanup</li>
  </ol>

  <h3>Importing Tags</h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>

    <p><?php printf(__('About to import <strong>%s tags</strong>.'), count($tags)) ?></p>

    <p class="submit">
      <input type="submit" name="submit" class="button" value="<?php echo esc_attr__('Import Tags') ?>" />
    </p>
  </form>
</div>