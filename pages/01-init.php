<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <p><?php _e("You are only a few steps to your Canalblog to WordPress migration. Please fill in the fields and press <em>Start import</em>.", 'canalblog-importer') ?></p>

  <p><strong><?php _e('Import Steps', 'canalblog-importer') ?></strong></p>
  <ol>
    <li><strong><?php _e('Configuration', 'canalblog-importer') ?></strong></li>
    <li><?php _e('Tags', 'canalblog-importer') ?></li>
    <li><?php _e('Categories', 'canalblog-importer') ?></li>
    <li><?php _e('Archives', 'canalblog-importer') ?></li>
    <li><?php _e('Posts, comments and media', 'canalblog-importer') ?></li>
    <li><?php _e('Cleanup', 'canalblog-importer') ?></li>
  </ol>

  <h3><?php _e('Configuration', 'canalblog-importer') ?></h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <table class="form-table">
      <tbody>
        <tr>
          <th><label for="canalblog_uri"><?php _e('Blog URL') ?></label></th>
          <td style="vertical-align: top">
            <input type="text" value="" name="blog_url" id="canalblog_uri" /><br />
            <span class="help"><?php _e('Example: http://yourblog.canalblog.com', 'canalblog-importer') ?></span>
          </td>
          <td rowspan="7" style="text-align: left">
            <img src="<?php echo $this->configuration->getPluginUri() ?>/assets/images/canalblog-configuration.png" alt="" />
          </td>
        </tr>
        <tr>
          <th><label for="canalblog_overwrite_contents"><?php _e('Overwrite existing contents', 'canalblog-importer') ?></label></th>
          <td style="vertical-align: top">
            <input type="checkbox" value="1" name="overwrite_contents" id="canalblog_overwrite_contents" />
            <span class="help"><?php _e('If checked, already imported contents will be updated instead of being skipped.', 'canalblog-importer') ?></span>
          </td>
        </tr>
        <tr>
          <th><label for="canalblog_default_comments_status"><?php _e('Default Comments Status', 'canalblog-importer') ?></label></th>
          <td style="vertical-align: top">
            <select name="comments_status" id="canalblog_default_comments_status">
              <option value="open" selected="selected"><?php _e('Opened', 'canalblog-importer') ?></option>
              <option value="closed"><?php _e('Closed', 'canalblog-importer') ?></option>
            </select><br />
            <span class="help"><?php _e('It will be the default status for imported articles.', 'canalblog-importer') ?></span>
          </td>
        </tr>
        <tr>
          <th><label for="canalblog_default_trackback_status"><?php _e('Default Trackback Status', 'canalblog-importer') ?></label></th>
          <td style="vertical-align: top">
            <select name="trackback_status" id="canalblog_default_trackback_status">
              <option value="open" selected="selected"><?php _e('Opened', 'canalblog-importer') ?></option>
              <option value="closed"><?php _e('Closed', 'canalblog-importer') ?></option>
            </select><br />
            <span class="help"><?php _e('It will be the default status for imported articles.', 'canalblog-importer') ?></span>
          </td>
        </tr>
        <tr>
          <th><?php _e('Canalblog settings', 'canalblog-importer') ?></th>
          <td style="vertical-align: top">
            <span class="help"><?php _e('Please ensure your Canalblog blog is configured with these settings:', 'canalblog-importer') ?></span>
            <ul>
              <li><?php printf(__('Day formatting: %s', 'canalblog-importer'), date_i18n('d F Y')) ?></li>
              <li><?php printf(__('Hour formatting: %s', 'canalblog-importer'), date_i18n('H:i')) ?></li>
              <li><?php _e('Digest format: Monthly', 'canalblog-importer') ?></li>
            </ul>

            <a href="http://www.canalblog.com/cf/my/" style="font-weight:bold"><?php _e("Configure Canalblog settings", 'canalblog-importer') ?></a>
          </td>
        </tr>
        <tr>
          <th><?php _e("Permalink structure", 'canalblog-importer') ?></th>
          <td style="vertical-align: top">
            <span class="help"><?php _e("The importer will rewrite all your internal links (from a post to another). It can be done only once so check your permalink structure <em>before</em> importing anything.", 'canalblog-importer') ?><br />
            <?php _e("Best permalink structure is at least the <em>month and title</em> one (<code>2010/02/sample-post/</code>).", 'canalblog-importer') ?></span>

            <br /><a href="options-permalink.php" style="font-weight:bold"><?php _e('Configure permalink structure', 'canalblog-importer') ?></a>
          </td>
        </tr>
        <tr>
          <th><?php _e("Thumbnail size", 'canalblog-importer') ?></th>
          <td style="vertical-align: top">
            <span class="help"><?php _e("The importer will retrieve <em>all</em> your pictures and will use the <strong>medium size</strong> format as a replacement within your posts (with a link to full size pictures).", 'canalblog-importer') ?></span>

            <br /><a href="options-media.php" style="font-weight:bold"><?php _e('Configure thumbnail size', 'canalblog-importer') ?></a>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__('Start import', 'canalblog-importer') ?>" />
    </p>
  </form>
</div>