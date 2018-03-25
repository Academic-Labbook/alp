<p><?php _e( 'Load the <a href="https://www.mathjax.org/">MathJax</a> JavaScript library from the following URL:', 'ssl-alp' ); ?></p>
<input name="ssl_alp_mathjax_url" id="ssl_alp_mathjax_url_textbox" value="<?php echo esc_url( get_option( 'ssl_alp_mathjax_url' ) ); ?>" class="large-text code" type="url">
<p class="description"><?php _e(' For self-hosted scripts, this can be a relative URL.', 'ssl-alp' ); ?></p>
