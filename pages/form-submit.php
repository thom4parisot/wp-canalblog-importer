		<p class="submit">
      <input type="button" class="button-primary start-remote-operation" value="<?php echo esc_attr__('Start Import', 'canalblog-importer') ?>" />
      <input type="submit" class="button-primary next-operation hidden" value="<?php echo esc_attr__('Next Step &rarr;', 'canalblog-importer') ?>" />
      <a href="<?php echo wp_nonce_url('import.php?import=canalblog&cancel=1', 'import-canalblog-cancel') ?>" class="button"><?php echo esc_attr__('Cancel', 'canalblog-importer') ?></a>
    </p>