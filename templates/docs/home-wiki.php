<?php
/**
 * BuddyPress Docs Wiki Home - Template Part
 */
?>

	<div class="wiki-home">
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


		<?php bp_get_template_part( 'docs/docs-loop' ); ?>

	</div><!-- .wiki-home -->
