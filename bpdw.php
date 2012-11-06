<?php

/**
 * Todo:
 * - Wiki homepage template
 * - Page <title>
 * - Widgets
 */

if ( ! defined( 'BP_DOCS_WIKI_SLUG' ) ) {
	define( 'BP_DOCS_WIKI_SLUG', 'wiki' );
}

// Rewrites
add_filter( 'query_vars',                     'bpdw_query_vars' );
add_filter( 'generate_rewrite_rules',         'bpdw_generate_rewrite_rules' );

// Taxonomy
add_action( 'bp_docs_init',                   'bpdw_register_taxonomy' );

// Create/edit interface
add_filter( 'bp_docs_allow_associated_group', 'bpdw_allow_associated_group' );
add_filter( 'bp_docs_allow_access_settings',  'bpdw_allow_access_settings' );

// Directories
add_filter( 'bp_docs_pre_query_args',         'bpdw_filter_query_args' );
add_filter( 'bp_docs_locate_template',        'bpdw_filter_home_template', 10, 2 );
add_action( 'widgets_init',                   'bpdw_register_sidebars' );
add_action( 'wp_enqueue_scripts',             'bpdw_enqueue_styles' );

// Metadata
add_action( 'bp_docs_doc_saved',              'bpdw_save_metadata' );

// Redirection
add_action( 'bp_screens',                     'bpdw_maybe_redirect' );

// Translations
add_filter( 'gettext',                        'bpdw_filter_gettext', 10, 3 );


/**
 * Returns the BuddyPress Docs Wiki slug - 'wiki'
 */
function bpdw_slug() {
	return BP_DOCS_WIKI_SLUG;
}

/**
 * Are we looking at a wiki page?
 */
function bpdw_is_wiki() {
	global $wp_query;
	return 1 == $wp_query->get( 'bpdw_is_wiki' );
}

/**
 * Are we looking at the wiki home page?
 */
function bpdw_is_wiki_home() {
	global $wp_query;
	return 1 == $wp_query->get( 'bpdw_is_wiki_home' );
}

/**
 * Is a given doc a Wiki doc?
 */
function bpdw_is_wiki_doc( $doc_id ) {
	$is_wiki_doc = false;

	$terms = wp_get_post_terms( $doc_id, 'bpdw_is_wiki' );

	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( 1 == $term->name ) {
				$is_wiki_doc = true;
				break;
			}
		}
	}

	return $is_wiki_doc;
}

/**
 * Adds 'bpdw_is_wiki' to public query vars, to assit with rewrites
 */
function bpdw_query_vars( $vars ) {
	$vars[] = 'bpdw_is_wiki';
	$vars[] = 'bpdw_is_wiki_home';
	return $vars;
}

/**
 * Generates custom rewrite rules
 */
function bpdw_generate_rewrite_rules( $wp_rewrite ) {
	$rules = array(
		bpdw_slug() . '/' . BP_DOCS_CREATE_SLUG . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_CREATE_SLUG . '=1' . '&bpdw_is_wiki=1',
		bpdw_slug() . '/([^/]+)/' . BP_DOCS_EDIT_SLUG . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_EDIT_SLUG . '=1' . '&bpdw_is_wiki=1',
		bpdw_slug() . '/([^/]+)/' . BP_DOCS_HISTORY_SLUG . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1' . '&bpdw_is_wiki=1',
		bpdw_slug() . '/([^/]+)/' . BP_DOCS_DELETE_SLUG . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1' . '&bpdw_is_wiki=1',
		bpdw_slug() . '(.+?)(/[0-9]+)?/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&bpdw_is_wiki=1',
		bpdw_slug() . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&bpdw_is_wiki=1&bpdw_is_wiki_home=1',
	);

	$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	return $wp_rewrite;
}

/**
 * Registers our 'bpdw_is_wiki' taxonomy
 */
function bpdw_register_taxonomy() {
	register_taxonomy( 'bpdw_is_wiki', bp_docs_get_post_type_name(), array(
		'public'    => false,
		'query_var' => false,
	) );
}

/**
 * Don't allow Wiki pages to be associated with groups
 */
function bpdw_allow_associated_group( $allow ) {
	if ( bpdw_is_wiki() ) {
		$allow = false;
	}

	return $allow;
}

/**
 * Don't allow Wiki pages to have their access settings set
 */
function bpdw_allow_access_settings( $allow ) {
	if ( bpdw_is_wiki() ) {
		$allow = false;
	}

	return $allow;
}

/**
 * Make sure that only wiki pages appear on wiki directories, and that no
 * wiki pages appear on non-wiki directories
 */
function bpdw_filter_query_args( $args ) {
	if ( bpdw_is_wiki() ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'bpdw_is_wiki',
			'terms'    => '1',
			'operator' => 'IN',
			'field'    => 'name',
		);
	} else {
		$args['tax_query'][] = array(
			'taxonomy' => 'bpdw_is_wiki',
			'terms'    => '1',
			'operator' => 'NOT IN',
			'field'    => 'name',
		);
	}

	return $args;
}

function bpdw_filter_home_template( $template_path, $template ) {
	if ( bpdw_is_wiki_home() && 'archive-bp_doc.php' == $template ) {
		$child  = get_stylesheet_directory();
		$parent = get_template_directory();

		if ( file_exists( trailingslashit( $child ) . 'docs/wiki-home.php' ) ) {
			$template_path = trailingslashit( $child ) . 'docs/wiki-home.php';
		} else if ( file_exists( trailingslashit( $parent ) . 'docs/wiki-home.php' ) ) {
			$template_path = trailingslashit( $parent ) . 'docs/wiki-home.php';
		} else {
			$template_path = trailingslashit( dirname(__FILE__) ) . 'wiki-home.php';
		}
	}
	return $template_path;
}

/**
 * When a Doc is saved, make sure that the metadata and taxonomy are Wiki-ish
 */
function bpdw_save_metadata( $docs_query ) {
	if ( ! bpdw_is_wiki() ) {
		return;
	}

	// Mark as a wiki item
	wp_set_post_terms( $docs_query->doc_id, '1', 'bpdw_is_wiki' );

	// Save the proper access settings
	update_post_meta( $docs_query->doc_id, 'bp_docs_settings', array(
		'read'          => 'anyone',
		'edit'          => 'loggedin',
		'read_comments' => 'anyone',
		'post_comments' => 'loggedin',
		'view_history'  => 'anyone',
	) );

	// Mark the proper access level tax, just to be safe
	bp_docs_update_doc_access( $docs_query->doc_id, 'anyone' );
}

/**
 * When loading a doc, make sure you're viewing it under the correct top-level
 * url (docs or wiki). Redirect if necessary
 */
function bpdw_maybe_redirect() {
	if ( bp_docs_is_existing_doc() ) {
		$is_wiki_url = bpdw_is_wiki();
		$is_wiki_doc = bpdw_is_wiki_doc( get_the_ID() );

		if ( $is_wiki_url && ! $is_wiki_doc ) {
			$redirect_to = str_replace( home_url( bpdw_slug() ), home_url( bp_docs_get_slug() ), get_permalink() );
		} else if ( ! $is_wiki_url && $is_wiki_doc ) {
			$redirect_to = str_replace( home_url( bp_docs_get_slug() ), home_url( bpdw_slug() ), get_permalink() );
		}

		if ( isset( $redirect_to ) ) {
			bp_core_redirect( $redirect_to );
		}
	}
}

/**
 * Catch the text on the way through gettext, and translate
 */
function bpdw_filter_gettext( $translation, $text, $domain ) {

	if ( 'bp-docs' != $domain || ! bpdw_is_wiki() ) {
		return $translation;
	}

	switch( $text ){
		case 'Tags are words or phrases that help to describe and organize your Docs.':
			return __( 'Tags are words or phrases that help to describe and organize your wiki pages.', 'bp-docs-wiki' );
			break;
		case 'Select a parent for this Doc.':
			return __( 'Select a parent for this wiki page.', 'bp-docs-wiki' );
			break;
		case '(Optional) Assigning a parent Doc means that a link to the parent will appear at the bottom of this Doc, and a link to this Doc will appear at the bottom of the parent.' :
			return __( '(Optional) Assigning a parent that a link to the parent will appear at the bottom of this wiki page, and a link to this page will appear at the bottom of the parent.', 'bp-docs-wiki' );
			break;
		case 'There are no comments for this doc yet.':
			return __( 'There are no comments yet.', 'bp-docs-wiki' );
			break;
		case 'Create New Doc':
			return __( 'Create New Wiki Page', 'bp-docs-wiki' );
			break;
	}

	return $translation;
}

function bpdw_register_sidebars() {
	register_sidebar( array(
		'name'          => __( 'Wiki Top', 'bp-docs-wiki' ),
		'id'            => 'wiki-top',
		'description'   => __( 'The full-width area at the top of the Wiki home page', 'bp-docs-wiki' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3>',
		'after_title'   => '</h3>'
	) );

	register_sidebar( array(
		'name'          => __( 'Wiki Bottom Left', 'bp-docs-wiki' ),
		'id'            => 'wiki-bottom-left',
		'description'   => __( 'The half-width area at the bottom-left of the Wiki home page', 'bp-docs-wiki' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3>',
		'after_title'   => '</h3>'
	) );

	register_sidebar( array(
		'name'          => __( 'Wiki Bottom Right', 'bp-docs-wiki' ),
		'id'            => 'wiki-bottom-right',
		'description'   => __( 'The half-width area at the bottom-right of the Wiki home page', 'bp-docs-wiki' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3>',
		'after_title'   => '</h3>'
	) );

	register_sidebar( array(
		'name'          => __( 'Wiki Sidebar', 'bp-docs-wiki' ),
		'id'            => 'wiki-sidebar',
		'description'   => __( 'The sidebar on the Wiki home page', 'bp-docs-wiki' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3>',
		'after_title'   => '</h3>'
	) );
}

function bpdw_enqueue_styles() {
	if ( bpdw_is_wiki_home() ) {
		wp_enqueue_style( 'bp-docs-wiki-home', plugins_url() . '/buddypress-docs-wiki/wiki-home.css' );
	}
}

/**
 * This function is necessary if I want to provide my own sidebar template in
 * the plugin
 */
function bpdw_get_sidebar() {
	if ( ! $template = locate_template( 'sidebar-bpdw.php' ) ) {
		$template = dirname(__FILE__) . '/sidebar-bpdw.php';
	}

	load_template( $template );
}
