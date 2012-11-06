<?php

/**
 * BuddyPress Docs Wiki Home
 */

?>

<?php get_header( 'buddypress' ); ?>

	<div class="wiki-home" id="content">
		<div class="padder">

		<h2 class="page-title" id="wiki-title"><?php _e( 'Wiki', 'bp-docs-wiki' ) ?></h2>

		<div id="wiki-header">
			<?php bp_docs_create_button() ?>
			Search
		</div>

		<div class="wiki-home-sidebar" id="wiki-top">
			<?php dynamic_sidebar( 'wiki-top' ) ?>
		</div>

		<div id="wiki-bottom">
			<div class="wiki-home-sidebar" id="wiki-bottom-left">
				<?php dynamic_sidebar( 'wiki-bottom-left' ) ?>
			</div>

			<div class="wiki-home-sidebar" id="wiki-bottom-right">
				<?php dynamic_sidebar( 'wiki-bottom-right' ) ?>
			</div>
		</div>

		</div><!-- .padder -->
	</div><!-- #content -->

<?php bpdw_get_sidebar(); ?>
<?php get_footer( 'buddypress' ); ?>

