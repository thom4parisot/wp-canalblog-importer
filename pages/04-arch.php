<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer') ?></h2>

  <p><strong><?php _e('Import Steps') ?></strong></p>
  <ol>
    <li><?php _e('Configuration') ?></li>
    <li><?php _e('Tags') ?></li>
    <li><?php _e('Categories') ?></li>
    <li><strong><?php _e('Archives') ?></strong></li>
    <li><?php _e('Cleanup') ?></li>
  </ol>

  <h3><?php _e('Archives') ?></h3>
  <p><?php _e('This step includes posts, comments, authors and attachments. This is done month by month so if you have a huge blog, be patient ;-)') ?></p>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php printf(__('<strong>%s months of posts</strong> to import − currently %s/%s.'), count($months), $months[$page]['month'], $months[$page]['year']) ?></p>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__('Import Archives') ?>" />
      <input type="submit" name="submit" class="button" value="<?php echo esc_attr__('Cancel') ?>" />
    </p>
  </form>
</div>