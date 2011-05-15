<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <h3><?php _e('Finished!', 'canalblog-importer') ?></h3>

  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php _e("Finally, you've made your way from Canalblog. Before I wish you a newly happy blogging, please check these settings:", 'canalblog-importer') ?></p>
    <ol>
      <li>
        <strong><?php _e('Users') ?></strong><br />
        <p><?php _e("Canalblog importer has created as many users as Canalblog authors with published posts. You may be counted in but it is a separate account that your current one: just delete any account to claim back its posts.", 'canalblog-importer') ?></p>

        <p style="text-align: center"><img src="<?php echo $this->configuration->getPluginUri() ?>/assets/images/canalblog-claim-posts.png" alt="" /></p>

        <br /><a href="users.php" target="_blank"><?php _e("Configure users", 'canalblog-importer') ?></a>
      </li>
      <li>
        <strong><?php _e('Blogroll') ?></strong><br />
        <p><?php _e("Canalblog importer is not able to retrieve your friends links list. You'll have to retype it from scratch. Sorry ;-(", 'canalblog-importer') ?></p>

        <br /><a href="link-manager.php" target="_blank"><?php _e("Configure blogroll", 'canalblog-importer') ?></a>
      </li>
      <li>
        <strong><?php _e('Search Engine Optimization', 'canalblog-importer') ?></strong><br />

        <p><?php _e("You changed your blog address without any possibility to redirect back your old blog to this one. Some advice:", 'canalblog-importer') ?></p>
        <ol>
          <li><?php _e("Tell your friends your blog address has changed... so as they will update links towards your new blog!", 'canalblog-importer') ?></li>
          <li><?php _e("Setup your Canalblog blog to hide from search engines (it's in Setup &gt; Syndication and ads). It will avoid duplicate content.", 'canalblog-importer') ?></li>
          <li><?php _e("Change your email signature and social profiles to update your blog address.", 'canalblog-importer') ?></li>
        </ol>
      </li>
      <li>
        <strong><?php _e("And finally...", 'canalblog-importer') ?></strong><br />

        <p><?php _e("Once you're done, delete your Canalblog account and <a href='http://www.canalblog.com/cf/contact.cfm' target='_blank'>write a mail to their team</a> explaining why you left. They have not evolved, you did.", 'canalblog-importer') ?></p>
      </li>
    </ol>

    <p class="submit">
      <a href="<?php echo wp_nonce_url('import.php?import=canalblog&cancel=1', 'import-canalblog-cancel') ?>" class="button button-primary next-operation"><?php echo esc_attr__('Done', 'canalblog-importer') ?></a>
      − <?php _e("I'm aware I've finally left Canalblog and this importer saved my life. Almost.", 'canalblog-importer') ?>
    </p>
  </form>
</div>