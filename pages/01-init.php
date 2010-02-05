<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer') ?></h2>

  <p><?php _e("You are only a few steps to your Canalblog to WordPress migration. Please fill in the fields and press <em>Start import</em>.") ?></p>

  <p><strong><?php _e('Import Steps') ?></strong></p>
  <ol>
    <li><strong>Configuration</strong></li>
    <li>Tags</li>
    <li>Categories</li>
    <li>Archives</li>
    <li>Cleanup</li>
  </ol>

  <h3>Configuration</h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>

    <table class="form-table">
      <tbody>
        <tr>
          <th><label for="canalblog_uri"><?php _e('Blog URL') ?></label></th>
          <td>
            <input type="text" value="" name="blog_url" id="canalblog_uri" /><br />
            <span class="help"><?php _e('Example: http://yourblog.canalblog.com') ?></span>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="submit" class="button" value="<?php echo esc_attr__('Start import') ?>" />
    </p>
  </form>
</div>