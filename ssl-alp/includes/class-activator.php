<?php

/**
 * Fired during plugin activation.
 */
class SSL_ALP_Activator {
	/**
	 * Activate plugin.
	 */
	public static function activate() {
		global $alp;

		// flush rewrite rules for wiki
		$alp->wiki->register_wiki_post_type();
        flush_rewrite_rules();
	}
}
