<?php

/**
 * Fired during plugin deactivation.
 */
class SSL_ALP_Deactivator {
	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		global $alp;

		// flush rewrite rules for wiki
		$alp->wiki->register_wiki_post_type();
		flush_rewrite_rules();
	}
}
