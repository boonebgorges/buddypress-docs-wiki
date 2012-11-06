<?php

/**
 * BuddyPress Docs Wiki Home
 */

?>

<?php get_header( 'buddypress' ); ?>

	<div class="wiki-home" id="content">
		<div class="padder">

		<h3 class="page-title" id="wiki-title"><?php _e( 'Wiki', 'bp-docs-wiki' ) ?></h3>

		<div id="wiki-header">
			<div class="doc-search" id="wiki-header-search">
				<form action="<?php echo home_url( 'wiki/browse/' ) ?>" method="get">
					<input name="s" value="<?php the_search_query() ?>">
					<input type="submit" value="<?php _e( 'Search', 'bp-docs' ) ?>" />
				</form>
			</div>

			<div id="wiki-header-create">
				<?php bp_docs_create_button() ?>
			</div>
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

