<?php
/**
 * Todo:
 * - Nav menu, esp current state
 */

if ( ! defined( 'BP_DOCS_WIKI_SLUG' ) ) {
	define( 'BP_DOCS_WIKI_SLUG', 'wiki' );
}

// Rewrites
add_filter( 'query_vars',                     'bpdw_query_vars' );
add_filter( 'generate_rewrite_rules',         'bpdw_generate_rewrite_rules' );

// Taxonomy
add_action( 'bp_docs_init',                   'bpdw_register_taxonomy' );
add_filter( 'bp_docs_taxonomy_get_item_terms', 'bpdw_get_item_terms', 5 );

// Create/edit interface
add_filter( 'bp_docs_allow_associated_group', 'bpdw_allow_associated_group' );
add_filter( 'bp_docs_allow_access_settings',  'bpdw_allow_access_settings' );

// Permissions
add_filter( 'bp_docs_attachment_upload_is_doc', 'bpdw_attachment_upload_is_doc', 10, 2 );

// Directories
add_filter( 'bp_docs_pre_query_args',         'bpdw_filter_query_args' );
add_action( 'widgets_init',                   'bpdw_register_sidebars' );
add_action( 'wp_enqueue_scripts',             'bpdw_enqueue_styles' );
add_action( 'bp_docs_sidebar_template',	      'bpdw_filter_bp_docs_sidebar' );
add_action( 'bp_screens',                     'bpdw_remove_group_column',     5 );
add_action( 'bp_screens',                     'bpdw_screen' );
add_filter( 'bp_get_root_template',           'bpdw_wiki_bypass_theme_compat_template' );
add_filter( 'bp_get_template_part',           'bpdw_wiki_home_template_part', 10, 2 );

// Widgets
add_action( 'widgets_init',                   'bpdw_widgets_init' );

// Metadata/taxonomy
add_action( 'bp_docs_doc_saved',              'bpdw_save_metadata' );
add_action( 'bp_docs_taxonomy_saved',         'bpdw_mirror_tags' );

// Redirection
add_action( 'bp_screens',                     'bpdw_maybe_redirect' );
add_filter( 'bp_docs_get_doc_link',           'bpdw_filter_doc_link', 10, 2 );
add_filter( 'bp_docs_get_archive_link',       'bpdw_filter_archive_link' );

// Translations
add_action( 'init',                           'bpdw_localization',   0 );
add_filter( 'gettext',                        'bpdw_filter_gettext', 10, 3 );

// Page title, class, nav menu
add_filter( 'body_class',                     'bpdw_filter_body_class' );
add_filter( 'wp_title',                       'bpdw_filter_page_title' );
add_filter( 'nav_menu_css_class',             'bpdw_filter_current_nav_menu', 10, 2 );
add_filter( 'bp_docs_allow_comment_section',  'bpdw_allow_comment_section' );

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

	// This should already have been pulled up
	$cached_is_wiki_term = wp_cache_get( $doc_id, 'bpdw_is_wiki_relationships' );

	if ( false === $cached_is_wiki_term ) {
		$docs = array( get_post( $doc_id ) );
		update_post_caches( $docs, bp_docs_get_post_type_name(), true, false );
		$cached_is_wiki_term = wp_cache_get( $doc_id, 'bpdw_is_wiki_relationships' );
	}

	$is_wiki_doc = false;
	if ( ! empty( $cached_is_wiki_term ) ) {
		$cached_term = array_pop( $cached_is_wiki_term );
		if ( 1 == $cached_term->slug ) {
			$is_wiki_term = true;
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
		bpdw_slug() . '/browse/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&bpdw_is_wiki=1',
		bpdw_slug() . '/browse/page/[0-9]+?/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&paged=' . $wp_rewrite->preg_index( 1 ) . '&bpdw_is_wiki=1',
		bpdw_slug() . '/(.+?)(/[0-9]+)?/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&name=' . $wp_rewrite->preg_index( 1 ) . '&bpdw_is_wiki=1',
		bpdw_slug() . '/?$' =>
			'index.php?post_type=' . bp_docs_get_post_type_name() . '&bpdw_is_wiki=1&bpdw_is_wiki_home=1',
	);

	$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	return $wp_rewrite;
}

/**
 * Registers our 'bpdw_is_wiki' taxonomy
 *
 * Also registers the shadow bpdw_tag taxonomy, which tracks wiki page tags
 * for the purpose of wiki-specific tag clouds
 */
function bpdw_register_taxonomy() {
	register_taxonomy( 'bpdw_is_wiki', bp_docs_get_post_type_name(), array(
		'public'    => false,
		'query_var' => false,
	) );

	register_taxonomy( 'bpdw_tag', bp_docs_get_post_type_name(), array(
		'public'    => false,
		'query_var' => false,
	) );
}

/**
 * Filter the terms available when looking at a Docs or Wiki directory
 */
function bpdw_get_item_terms( $terms ) {
	// Get all doc ids
	$item_ids = bp_docs_get_doc_ids_accessible_to_current_user();

	// But then filter according to wiki status
	$wiki_items = get_posts( array(
		'post_type' => bp_docs_get_post_type_name(),
		'tax_query' => array( bpdw_tax_query_iswiki() ),
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'nopaging' => true,
		'posts_per_page' => -1,
	) );
	$wiki_item_ids = wp_list_pluck( $wiki_items, 'ID' );

	if ( bpdw_is_wiki() ) {
		$item_ids = array_intersect( $item_ids, $wiki_item_ids );
	} else {
		$item_ids = array_diff( $item_ids, $wiki_item_ids );
	}

	// Have to do it one at a time so we have accurate wiki/doc-only counts
	$all_terms = array();
	update_post_caches( $wiki_items, bp_docs_get_post_type_name(), true, false );

	foreach ( $item_ids as $item_id ) {
		// This data should be cached
		$cached_terms = wp_cache_get( $item_id, 'bp_docs_tag_relationships' );
		if ( ! empty( $cached_terms ) ) {
			foreach ( $cached_terms as $t ) {
				if ( ! isset( $all_terms[ $t->slug ] ) ) {
					$all_terms[ $t->slug ] = array(
						'name' => $t->name,
						'posts' => array(),
					);
				}

				if ( ! in_array( $item_id, $all_terms[ $t->slug ]['posts'] ) ) {
					$all_terms[ $t->slug ]['posts'][] = $item_id;
				}
			}
		}
	}

	foreach ( $all_terms as &$at ) {
		$at['count'] = count( $at['posts'] );
	}

	unset( $items, $cached_terms );

	// Don't allow BP Docs to do its native directory filtering
	remove_action( 'bp_docs_taxonomy_get_item_terms', array( buddypress()->bp_docs, 'get_item_terms' ) );

	return $all_terms;
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
 * Ensures that Wiki Create page registers as a Doc during attachment upload
 *
 * BuddyPress Docs does a trick to enable the upload_files cap for non-admin
 * users; see BP_Docs_Attachments::map_meta_cap(). Part of this trick requires
 * determining whether the HTTP_REFERER is the Docs create page. But this check
 * fails when BPDW has swapped out the 'docs' slug for the 'wiki' slug. In this
 * function, we manually filter the result of the "is_doc" check, and use our
 * own logic for checking the HTTP_REFERER.
 *
 * @since 1.0.5
 */
function bpdw_attachment_upload_is_doc( $is_doc, $is_ajax ) {
	if ( $is_ajax ) {
		$is_doc = $_SERVER['HTTP_REFERER'] === trailingslashit( home_url( bpdw_slug() ) ) . 'create/';
	}

	return $is_doc;
}

/**
 * Make sure that only wiki pages appear on wiki directories, and that no
 * wiki pages appear on non-wiki directories
 */
function bpdw_filter_query_args( $args ) {
	if ( bpdw_is_wiki() ) {
		$args['tax_query'][] = bpdw_tax_query_iswiki();
	} else {
		$args['tax_query'][] = bpdw_tax_query_isnotwiki();
	}

	return $args;
}

/**
 * Add the iswiki tax_query arg to a set of query args
 */
function bpdw_tax_query_iswiki_cb( $args ) {
	$args['tax_query'][] = bpdw_tax_query_iswiki();
	return $args;
}

/**
 * Add the isnotwiki tax_query arg to a set of query args
 */
function bpdw_tax_query_isnotwiki_cb( $args ) {
	$args['tax_query'][] = bpdw_tax_query_isnotwiki();
	return $args;
}

function bpdw_tax_query_iswiki() {
	return array(
		'taxonomy' => 'bpdw_is_wiki',
		'terms'    => '1',
		'operator' => 'IN',
		'field'    => 'name',
	);
}

function bpdw_tax_query_isnotwiki() {
	return array(
		'taxonomy' => 'bpdw_is_wiki',
		'terms'    => '1',
		'operator' => 'NOT IN',
		'field'    => 'name',
	);
}

/**
 * Bypass BP Theme Compat's 'the_content' injection for the wiki homepage
 * template and use another template as the top-level template.
 *
 * Will try to find the 'docs/index-wiki.php' template in the parent or child
 * theme.  If this template is found in the theme, it will be used.  If no
 * template is found in the theme, BP Docs Wiki will fallback to theme compat.
 *
 * The majority of theme devs will not need to use this template.  This
 * should only be used in specific cases.
 *
 * Hooked to 'bp_get_root_template' as that prevents 'the_content' injection.
 *
 * @since 1.0.4
 *
 * @return mixed Absolute path to root template on success; boolean false on failure.
 */
function bpdw_wiki_bypass_theme_compat_template( $template ) {
	if ( ! bpdw_is_wiki_home() ) {
		return $template;
	}

	return bp_locate_template( 'docs/index-wiki.php', false, false );
}

/**
 * Do some stuff while on the wiki homepage screen.
 *
 * 1) Register our custom template directories with BP's template stack.
 *
 * We register BP Docs' template directory on the chance that we might
 * want to use one of its templates like /docs/docs-loop.php.
 *
 * This is so we can provide fallback templates from our plugin directory if
 * the current theme did not override the template in question.
 *
 * 2) Filter the_title.
 *
 * Title uses 'Docs Directory' by default.  Change context to 'Wiki'.
 *
 * @since 1.0.4
 */
function bpdw_screen() {
	if ( bpdw_is_wiki_home() ) {
		// Register custom template directories
		bp_register_template_stack( 'bpdw_get_template_directory',      14 );
		bp_register_template_stack( 'bpdw_docs_get_template_directory', 14 );

		// Also filter the title while we're here
		add_filter( 'the_title', 'bpdw_filter_title' );
	}
}

/**
 * Returns our plugin's directory where custom templates are located.
 *
 * @since 1.0.4
 *
 * @return string
 */
function bpdw_get_template_directory() {
	return trailingslashit( dirname(__FILE__) ) . 'templates';
}

/**
 * Returns BP Docs' plugin template directory.
 *
 * @since 1.0.4
 *
 * @return string
 */
function bpdw_docs_get_template_directory() {
	return BP_DOCS_INCLUDES_PATH . '/templates';
}

/**
 * By default, the wiki home template part uses the BP Docs directory
 * template part - 'docs/docs-loop.php'
 *
 * We want to change this to use a custom template part - 'docs/home-wiki.php'
 *
 * @since 1.0.4
 */
function bpdw_wiki_home_template_part( $templates, $slug ) {
	// check if we're on our wiki homepage
	if ( ! bpdw_is_wiki_home() ) {
		return $templates;
	}

	// if the current theme supports 'buddypress', we want to be able to use
	// the docs-loop template immediately
	if ( current_theme_supports( 'buddypress' ) || $slug != 'docs/docs-loop' ) {
		return $templates;
	}

	// after we've told BP to use our custom wiki homepage template part, we want
	// to be able to run the docs-loop.php template for real.
	//
	// to do this, we check our special marker to see if we've already run this
	// function before.  if so, we can run the docs-loop.php template.
	if ( ! empty( buddypress()->wikihome->runonce ) ) {
		return $templates;
	}

	// add a marker to see if we've run this function before
	if ( empty( buddypress()->wikihome ) ) {
		buddypress()->wikihome = new stdClass;
		buddypress()->wikihome->runonce = 1;
	}

	// return our custom wiki homepage template part
	return array(
		'docs/home-wiki.php'
	);
}

/**
 * Filter the_title on the wiki homepage.
 *
 * @since 1.0.4
 *
 * @return string
 */
function bpdw_filter_title( $retval ) {
	switch( $retval ) {
		case __( 'Docs Directory', 'buddypress' ) :
			return __( 'Wiki', 'bpdw' );

			break;

		default :
			return $retval;

			break;
	}
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

function bpdw_mirror_tags( $query ) {
	if ( bpdw_is_wiki_doc( $query->doc_id ) ) {
		// Separate out the terms
		$terms = ! empty( $_POST['bp_docs_tag'] ) ? explode( ',', $_POST['bp_docs_tag'] ) : array();

		// Strip whitespace from the terms
		foreach ( $terms as $key => $term ) {
			$terms[$key] = trim( $term );
		}

		wp_set_post_terms( $query->doc_id, $terms, 'bpdw_tag' );
	}
}

/**
 * Get the canonical address for a Doc
 */
function bpdw_canonical_address( $doc_id = false ) {
	global $wp_query;

	if ( ! $doc_id && bp_docs_is_existing_doc() ) {
		$doc_id = $wp_query->post->ID;
	}

	$is_wiki_doc = bpdw_is_wiki_doc( $doc_id );

	if ( $is_wiki_doc ) {
		$url = str_replace( home_url( bp_docs_get_slug() ), home_url( bpdw_slug() ), get_permalink( $doc_id ) );
	} else {
		$url = str_replace( home_url( bpdw_slug() ), home_url( bp_docs_get_slug() ), get_permalink( $doc_id ) );
	}

	return $url;
}

/**
 * When loading a doc, make sure you're viewing it under the correct top-level
 * url (docs or wiki). Redirect if necessary
 */
function bpdw_maybe_redirect() {
	global $wp_query;

	if ( bp_docs_is_existing_doc() ) {
		$canonical = bpdw_canonical_address();
		$current   = set_url_scheme( trailingslashit( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );
		$change = 0 !== strpos( $current, $canonical );

		if ( $change ) {
			$redirect_to = str_replace( get_permalink( $wp_query->post->ID), $canonical, $current );
			bp_core_redirect( $redirect_to );
		}
	}
}

/**
 * Filter doc links to make sure the're canonical
 */
function bpdw_filter_doc_link( $link, $doc_id ) {
	return trailingslashit( bpdw_canonical_address( $doc_id ) );
}

function bpdw_filter_archive_link( $link ) {
	if ( bpdw_is_wiki() ) {
		$link = trailingslashit( home_url( bpdw_slug() ) );
	}
	return $link;
}

/**
 * Catch the text on the way through gettext, and translate
 */
function bpdw_filter_gettext( $translation, $text, $domain ) {

	if ( 'bp-docs' != $domain || ! bpdw_is_wiki() ) {
		return $translation;
	}

	switch( $text ){
		case 'New Doc':
			return __( 'New Wiki Page', 'bp-docs-wiki' );
			break;
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
		case 'Docs Directory':
			return __( 'Wiki', 'bp-docs-wiki' );
			break;
		case 'You are viewing docs with the following tags: %s' :
			return __( 'You are viewing wiki pages with the following tags: %s', 'bp-docs-wiki' );
			break;
		case 'You are searching for docs containing the term <em>%s</em>' :
			return __( 'You are searching for wiki pages containing the term <em>%s</em>', 'bp-docs-wiki' );
			break;
		case '<strong><a href="%s" title="View All Docs">View All Docs</a></strong>' :
			return __( '<strong><a href="%s" title="Remove Filter">Remove Filter</a></strong>', 'bp-docs-wiki' );
			break;
		case 'Docs tagged %s' :
			return __( 'Pages tagged %s', 'bp-docs-wiki' );
			break;
		case 'Viewing %1$s-%2$s of %3$s docs' :
			return __( 'Viewing %1$s-%2$s of %3$s pages', 'bp-docs-wiki' );
			break;
		case 'You are viewing <strong>all</strong> docs.' :
			return __( 'You are viewing <strong>all</strong> wiki pages.', 'bp-docs-wiki' );
			break;
	}

	return $translation;
}

/**
 * Custom textdomain loader.
 *
 * Checks WP_LANG_DIR for the .mo file first, then the plugin's language folder.
 * Allows for a custom language file other than those packaged with the plugin.
 *
 * @since 1.0.3
 *
 * @uses get_locale() To get the current locale
 * @uses load_textdomain() Loads a .mo file into WP
 * @return bool True on success, false on failure
 */
function bpdw_localization() {
	// Use the WP plugin locale filter from load_plugin_textdomain()
	$locale        = apply_filters( 'plugin_locale', get_locale(), 'bp-docs-wiki' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'bp-docs-wiki', $locale );

	$mofile_global = trailingslashit( constant( 'WP_LANG_DIR' ) ) . $mofile;
	$mofile_local  = trailingslashit( dirname( __FILE__ ) ) . 'languages/' . $mofile;

	// look in /wp-content/languages/ first
	if ( is_readable( $mofile_global ) ) {
		return load_textdomain( 'bp-docs-wiki', $mofile_global );

	// if that doesn't exist, check for bundled language file
	} elseif ( is_readable( $mofile_local ) ) {
		return load_textdomain( 'bp-docs-wiki', $mofile_local );

	// no language file exists
	} else {
		return false;
	}
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

	if ( current_theme_supports( 'buddypress' ) ) {
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
}

function bpdw_enqueue_styles() {
	if ( bpdw_is_wiki() ) {
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

function bpdw_filter_bp_docs_sidebar( $template ) {
	if ( bpdw_is_wiki() ) {
		if ( ! $template = locate_template( 'sidebar-bpdw.php' ) ) {
			$template = dirname(__FILE__) . '/sidebar-bpdw.php';
		}
	}

	return $template;
}

function bpdw_remove_group_column() {
	global $bp;

	if ( bpdw_is_wiki() ) {
		if ( isset( $bp->bp_docs->groups_integration ) && method_exists( $bp->bp_docs->groups_integration, 'groups_th' ) ) {
			remove_filter( 'bp_docs_loop_additional_th', array( $bp->bp_docs->groups_integration, 'groups_th' ), 5 );
			remove_filter( 'bp_docs_loop_additional_td', array( $bp->bp_docs->groups_integration, 'groups_td' ), 5 );
		}
	}
}

function bpdw_filter_body_class( $class ) {
	if ( bpdw_is_wiki() ) {
		$class[] = 'wiki';
	}
	return $class;
}

function bpdw_filter_page_title( $title ) {
	if ( bpdw_is_wiki() ) {
		$title = str_replace( __( 'BuddyPress Docs', 'bp-docs' ), __( 'Wiki', 'bp-docs-wiki' ), $title );
	}
	return $title;
}

function bpdw_allow_comment_section( $allow ) {
	if ( bpdw_is_wiki() ) {
		$allow = false;
	}
	return $allow;
}

function bpdw_filter_current_nav_menu( $classes, $item ) {
	if ( bpdw_is_wiki() ) {
		if ( trailingslashit( home_url( bpdw_slug() ) ) == trailingslashit( $item->url ) ) {
			$classes[] = 'current-menu-item';
		} else {
			$key = array_search( 'current-menu-item', $classes );
			if ( false !== $key ) {
				unset( $classes[ $key ] );
			}
		}
	}
	return $classes;
}

function bpdw_filter_term_link( $termlink, $term, $taxonomy ) {
	if ( 'bpdw_tag' == $taxonomy ) {
		$termlink = add_query_arg( 'bpd_tag', $term->slug, trailingslashit( home_url( bpdw_slug() ) ) . 'browse/' );
	}
	return $termlink;
}
add_filter( 'term_link', 'bpdw_filter_term_link', 10, 3 );

function bpdw_filter_term_link_widget( $termlink, $args, $item_docs ) {
	if ( bpdw_is_wiki() ) {
		// Bug in Docs filter arguments
		$tag = is_array( $args ) && isset( $args['tag'] ) ? $args['tag'] : $args;
		$termlink = add_query_arg( 'bpd_tag', $tag, trailingslashit( home_url( bpdw_slug() ) ) . 'browse/' );
	}
	return $termlink;
}
add_filter( 'bp_docs_get_tag_link_url', 'bpdw_filter_term_link_widget', 10, 3 );

function bpdw_widgets_init() {
	register_widget( 'BPDW_Recently_Active_Widget' );
	register_widget( 'BPDW_Most_Active_Widget' );
	register_widget( 'BPDW_Tag_Cloud_Widget' );
}

/**
 * Recently Active Wiki Pages
 */
class BPDW_Recently_Active_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bpdw_recently_active',
			__( '(Wiki) Recently Active Pages', 'bp-docs-wiki' ),
			array(
				'description' => __( 'A list of recently active wiki pages.', 'bp-docs-wiki' )
			)
		);
	}

	public function form( $instance ) {
		$defaults = array(
			'title'     => __( 'Recently Active', 'bp-docs-wiki' ),
			'max_pages' => 5,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title     = strip_tags( $instance['title'] );
		$max_pages = strip_tags( $instance['max_pages'] );

		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'bp-docs-wiki' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_pages' ) ?>"><?php _e('Number of posts to show:', 'bp-docs-wiki'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_pages' ); ?>" name="<?php echo $this->get_field_name( 'max_pages' ); ?>" type="text" value="<?php echo esc_attr( $max_pages ); ?>" style="width: 30%" /></label></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_pages'] = strip_tags( $new_instance['max_members'] );

		return $new_instance;
	}

	public function widget( $args, $instance ) {
		wp_enqueue_style( 'bp-docs-wiki-home', plugins_url() . '/buddypress-docs-wiki/wiki-home.css' );

		extract( $args );
		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title;

		$max_pages = isset( $instance['max_pages'] ) ? (int) $instance['max_pages'] : 5;

		$docs_args = array(
			'posts_per_page' => $max_pages,
			'orderby' => 'modified',
		);

		// Remove auto-filters, and ensure we only pull up wiki docs
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		add_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		$counter = 2; // Start with a weird number so as not to break the modulo
		bp_docs_reset_query();
		if ( bp_docs_has_docs( $docs_args ) ) {
			echo '<ul>';
			while ( bp_docs_has_docs() ) {
				bp_docs_the_doc();
				$zebra = $counter % 2 ? 'odd' : 'even';

				echo '<li class="' . $zebra . '">';
				echo '<div class="wiki-page-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></div>';
				echo '<div class="wiki-page-excerpt">' . get_the_excerpt() . '</div>';
				echo '</li>';

				$counter++;
			}
			echo '</ul>';
		}

		// Cleanup
		add_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		echo $after_widget;
	}


}

/**
 * Most Active Wiki Pages
 */
class BPDW_Most_Active_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bpdw_most_active',
			__( '(Wiki) Most Active Pages', 'bp-docs-wiki' ),
			array(
				'description' => __( 'A list of most active wiki pages.', 'bp-docs-wiki' )
			)
		);
	}

	public function form( $instance ) {
		$defaults = array(
			'title'     => __( 'Most Active', 'bp-docs-wiki' ),
			'max_pages' => 5,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title     = strip_tags( $instance['title'] );
		$max_pages = strip_tags( $instance['max_pages'] );

		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'bp-docs-wiki' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_pages' ) ?>"><?php _e('Number of posts to show:', 'bp-docs-wiki'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_pages' ); ?>" name="<?php echo $this->get_field_name( 'max_pages' ); ?>" type="text" value="<?php echo esc_attr( $max_pages ); ?>" style="width: 30%" /></label></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_pages'] = strip_tags( $new_instance['max_members'] );

		return $new_instance;
	}

	public function widget( $args, $instance ) {
		wp_enqueue_style( 'bp-docs-wiki-home', plugins_url() . '/buddypress-docs-wiki/wiki-home.css' );

		extract( $args );
		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title;

		$max_pages = isset( $instance['max_pages'] ) ? (int) $instance['max_pages'] : 5;

		// Remove auto-filters, and ensure we only pull up wiki docs
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		add_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		$docs_args = array(
			'posts_per_page' => $max_pages,
			'orderby' => 'most_active',
		);

		$counter = 2; // Start with a weird number so as not to break the modulo
		bp_docs_reset_query();
		if ( bp_docs_has_docs( $docs_args ) ) {
			echo '<ul>';
			while ( bp_docs_has_docs() ) {
				bp_docs_the_doc();
				$zebra = $counter % 2 ? 'odd' : 'even';

				echo '<li class="' . $zebra . '">';
				echo '<div class="wiki-page-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></div>';
				echo '<div class="wiki-page-excerpt">' . get_the_excerpt() . '</div>';
				echo '</li>';

				$counter++;
			}
			echo '</ul>';
		}

		// Cleanup
		add_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		echo $after_widget;
	}


}

/**
 * Wiki tag cloud widget
 */
class BPDW_Tag_Cloud_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bpdw_tag_cloud',
			__( '(Wiki) Tag Cloud', 'bp-docs-wiki' ),
			array(
				'description' => __( 'The most used tags on your wiki, in cloud format.', 'bp-docs-wiki' )
			)
		);
	}

	function widget( $args, $instance ) {
		extract($args);
		$current_taxonomy = 'bpdw_tag';

		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Wiki Tags', 'bp-docs-wiki' );
		}
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div class="tagcloud">';

		wp_tag_cloud( apply_filters('bpdw_widget_tag_cloud_args', array('taxonomy' => $current_taxonomy) ) );
		echo "</div>\n";
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['taxonomy'] = stripslashes($new_instance['taxonomy']);
		return $instance;
	}

	function form( $instance ) {
		$current_taxonomy = 'bpdw_tag';
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
	<?php
	}
}

/**
 * My Wiki Pages
 */
class BPDW_My_Pages_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bpdw_most_active',
			__( '(Wiki) Most Active Pages', 'bp-docs-wiki' ),
			array(
				'description' => __( 'A list of most active wiki pages.', 'bp-docs-wiki' )
			)
		);
	}

	public function form( $instance ) {
		$defaults = array(
			'title'     => __( 'Most Active', 'bp-docs-wiki' ),
			'max_pages' => 5,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title     = strip_tags( $instance['title'] );
		$max_pages = strip_tags( $instance['max_pages'] );

		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'bp-docs-wiki' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'max_pages' ) ?>"><?php _e('Number of posts to show:', 'bp-docs-wiki'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_pages' ); ?>" name="<?php echo $this->get_field_name( 'max_pages' ); ?>" type="text" value="<?php echo esc_attr( $max_pages ); ?>" style="width: 30%" /></label></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_pages'] = strip_tags( $new_instance['max_members'] );

		return $new_instance;
	}

	public function widget( $args, $instance ) {
		wp_enqueue_style( 'bp-docs-wiki-home', plugins_url() . '/buddypress-docs-wiki/wiki-home.css' );

		extract( $args );
		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title;

		$docs_args = array(
			'posts_per_page' => $instance['max_pages'],
			'orderby' => 'most_active',
		);

		// Remove auto-filters, and ensure we only pull up wiki docs
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		add_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		$counter = 2; // Start with a weird number so as not to break the modulo
		bp_docs_reset_query();
		if ( bp_docs_has_docs( $docs_args ) ) {
			echo '<ul>';
			while ( bp_docs_has_docs() ) {
				bp_docs_the_doc();
				$zebra = $counter % 2 ? 'odd' : 'even';

				echo '<li class="' . $zebra . '">';
				echo '<div class="wiki-page-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></div>';
				echo '<div class="wiki-page-excerpt">' . get_the_excerpt() . '</div>';
				echo '</li>';

				$counter++;
			}
			echo '</ul>';
		}

		// Cleanup
		add_filter( 'bp_docs_pre_query_args', 'bpdw_filter_query_args' );
		remove_filter( 'bp_docs_pre_query_args', 'bpdw_tax_query_iswiki_cb' );

		echo $after_widget;
	}
}

