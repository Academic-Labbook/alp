<div class="wrap">
	<h2><?php esc_html_e( 'Revisions', 'ssl-alp' ); ?></h2>
	<div id="ssl-alp-revisions-list-table">
		<?php $this->revisions_list_table->views(); ?>
		<form id="ssl-alp-revisions-list-form" method="get">
			<?php $this->revisions_list_table->display(); ?>
		</form>
	</div>
</div>
