<?php
/**
 * Plugin Name: Dynacat - Dynamic Category Filter
 * Plugin URI:
 * Description: Dynamic filtering of Categories
 * Author: Engage Web - Steven Morris
 * Version: 1.2
 * Author URI: https://www.engageweb.co.uk/about-us/meet-the-team#Steven
 * License: GPL2
 * Text Domain: dynacat
 *
 * @package DynaCat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return matching categories to the editor category search.
 */
function dynacat_ajax_check_cat() {
	check_ajax_referer( 'dynacat_category_search', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		status_header( 403 );
		wp_send_json_error( array( 'message' => __( 'You are not allowed to edit posts.', 'dynacat' ) ) );
	}

	$query = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
	if ( '' === $query ) {
		wp_send_json_success( array() );
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'name__like' => $query,
			'number'     => 30,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $categories ) ) {
		status_header( 500 );
		wp_send_json_error( array( 'message' => __( 'Categories could not be loaded.', 'dynacat' ) ) );
	}

	$results = array();
	foreach ( $categories as $category ) {
		$parent = get_category_parents( $category->term_id, false, ' &raquo; ' );
		if ( is_wp_error( $parent ) || $parent === $category->name ) {
			$parent = '';
		}

		$results[] = array(
			'id'    => $category->term_id,
			'name'  => $category->name,
			'label' => wp_specialchars_decode( $parent . $category->name, ENT_QUOTES ),
		);
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_check_cat', 'dynacat_ajax_check_cat' );

/**
 * Load DynaCat assets only on post editing screens.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function dynacat_admin_assets( $hook_suffix ) {
	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_style( 'dynacat', plugins_url( 'dynacatstyle.css', __FILE__ ), array(), '1.2' );
	wp_enqueue_script( 'dynacat', plugins_url( 'dynacat.js', __FILE__ ), array( 'jquery' ), '1.2', true );
	wp_localize_script(
		'dynacat',
		'dynacatSettings',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'dynacat_category_search' ),
			'loaderUrl' => plugins_url( 'ajax-loader.gif', __FILE__ ),
			'errorText' => __( 'Categories could not be loaded.', 'dynacat' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'dynacat_admin_assets' );

/**
 * Replace the post category meta box with DynaCat's searchable selector.
 */
function dynacat_add_theme_box() {
	add_meta_box( 'categorydiv', __( 'Categories', 'dynacat' ), 'dynacat_box', 'post', 'side', 'core' );
}
add_action( 'admin_menu', 'dynacat_add_theme_box' );

/**
 * Render the category selector.
 *
 * WordPress core verifies its post-edit nonce and handles the post_category field.
 *
 * @param WP_Post $post Post currently being edited.
 */
function dynacat_box( $post ) {
	$post_categories = wp_get_post_categories( $post->ID );
	$current_names   = array();
	$current_cat_id  = 0;

	foreach ( $post_categories as $category_id ) {
		$category_name = get_the_category_by_ID( $category_id );
		if ( ! is_wp_error( $category_name ) ) {
			$current_names[]  = $category_name;
			$current_cat_id   = $category_id;
		}
	}
	?>
	<p>
		<label><strong><?php esc_html_e( 'Current Category:', 'dynacat' ); ?></strong></label><br>
		<span id="dynacat-current-category"><?php echo esc_html( implode( ' ', $current_names ) ); ?></span>
	</p>
	<p>
		<label for="filbox"><strong><?php esc_html_e( 'New Category:', 'dynacat' ); ?></strong></label><br>
		<input type="text" name="filterbox" id="filbox" autocomplete="off">
		<input type="hidden" name="post_category[]" id="post_category" value="<?php echo esc_attr( $current_cat_id ); ?>">
	</p>
	<div id="result" aria-live="polite"></div>
	<?php
}
