<?php

/**
 * Plugin Name: Ryankit
 * Description: Custom block plugin 
 * Version: 1.2.4
 *
 * @package ryankit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load translations (if any) for the plugin from the /languages/ folder.
 * 
 * @link https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
 */
add_action( 'init', 'ryankit_load_textdomain' );

function ryankit_load_textdomain() {
	load_plugin_textdomain( 'ryankit', false, basename( __DIR__ ) . '/languages' );
}

/** 
 * Add custom image size for block featured image.
 * 
 * @link https://developer.wordpress.org/reference/functions/add_image_size/
 */
add_action( 'init', 'ryankit_add_image_size' );

function ryankit_add_image_size() {
	add_image_size( 'ryankitFeatImg', 250, 250, array( 'center', 'center' ) );
	add_image_size( 'ryankitFeatImg-2', 250, 250, array( 'center', 'center' ) );  
}

/** 
 * Register custom image size with sizes list to make it available.
 * 
 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/image_size_names_choose
 */
add_filter( 'image_size_names_choose', 'ryankit_custom_sizes' );


function ryankit_custom_sizes( $size_names ) {
    $new_sizes = array(
        'ryankitFeatImg' => __( 'Custom Size #1', 'generatewp.com' ),
        'ryankitFeatImg-2' => __( 'Custom Size #2', 'generatewp.com' ),
    );
    return array_merge( $size_names, $new_sizes );
}
add_filter( 'image_size_names_choose', 'ryankit_custom_sizes' );

/**
 * Add the featured image to the REST API response.
 */
add_filter( 'rest_prepare_post', 'ryankit_fetured_image_json', 10, 3 );

function ryankit_fetured_image_json( $data, $post, $context ) {
	// Get the featured image id from the REST API response.
	$featured_image_id = $data->data['featured_media']; 

	// Get the URL for a specific image size based on the image ID.
	$featured_image_url = wp_get_attachment_image_src( $featured_image_id, 'ryankitFeatImg' ); // get url of the original size

	// If we have a URL, add it to the REST API response.
	if( $featured_image_url ) {
		$data->data['featured_image_ryankitFeatImg_url'] = $featured_image_url[0];
	}

	return $data;
}

/** 
 * Add custom "Ryankit" block category
 * 
 * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/filters/block-filters/#managing-block-categories
 */
add_filter( 'block_categories', 'ryankit_block_categories', 10, 2 );

function ryankit_block_categories( $categories, $post ) {
	if ( $post->post_type !== 'post' ) {
		return $categories;
	}
	return array_merge(
		$categories,
		array(
			array(
				'slug' => 'ryankit',
				'title' => __( 'Ryankit', 'ryankit' ),
				'icon'  => 'microphone',
			),
		)
	);
}

/**
 * Registers all block assets so that they can be enqueued through the Block Editor in
 * the corresponding context.
 *
 * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-registration/
 */
add_action( 'init', 'ryankit_register_blocks' );

function ryankit_register_blocks() {

	// If Block Editor is not active, bail.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// Retister the block editor script.
	wp_register_script(
		'ryankit-editor-script',											// label
		plugins_url( 'build/index.js', __FILE__ ),						// script file
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', "wp-data" ),		// dependencies
		filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )		// set version as file last modified time
	);

	// Register the block editor stylesheet.
	wp_register_style(
		'ryankit-editor-styles',											// label
		plugins_url( 'build/editor.css', __FILE__ ),					// CSS file
		array( 'wp-edit-blocks' ),										// dependencies
		filemtime( plugin_dir_path( __FILE__ ) . 'build/editor.css' )	// set version as file last modified time
	);

	// Register the front-end stylesheet.
	wp_register_style(
		'ryankit-front-end-styles',										// label
		plugins_url( 'build/style.css', __FILE__ ),						// CSS file
		array( ),														// dependencies
		filemtime( plugin_dir_path( __FILE__ ) . 'build/style.css' )	// set version as file last modified time
	);

	// Array of block created in this plugin.
	$blocks = [
		'ryankit/media',

	];
	
	// Loop through $blocks and register each block with the same script and styles.
	foreach( $blocks as $block ) {
		register_block_type( $block, array(
			'editor_script' => 'ryankit-editor-script',					// Calls registered script above
			'editor_style' => 'ryankit-editor-styles',					// Calls registered stylesheet above
			'style' => 'ryankit-front-end-styles',						// Calls registered stylesheet above
		) );	  
	}

	// Register dynamic block.
	register_block_type( 'ryankit/dynamic', array(
		'editor_script' => 'ryankit-editor-script',
		'editor_style' => 'ryankit-editor-styles',
		'style' => 'ryankit-front-end-styles',
		'render_callback' => 'ryankit_dynamic_render_callback'
	) );

	if ( function_exists( 'wp_set_script_translations' ) ) {
	/**
	 * Adds internationalization support. 
	 * 
	 * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/internationalization/
	 * @link https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
	 */
	wp_set_script_translations( 'ryankit-editor-script', 'ryankit', plugin_dir_path( __FILE__ ) . '/languages' );
	}

}

/**
 * Build classes based on block attributes.
 * Returns string of classes.
 * 
 * $attributes - array - Block attributes.
 */
function ryankit_block_classes( $attributes ) {
	$classes = null;
	if ( $attributes['align'] ) {
		$classes = 'align' . $attributes['align'] . ' ';
	}

	if ( $attributes['className'] ) {
		$classes .= $attributes['className']; 
	}

	return $classes;
}

/**
 * Serve up featured image is available, otherwise serve up logo.
 * Returns <img> element.
 * 
 * $post - object - The post object.
 */ 
function ryankit_post_img( $post ) {
	$ryankit_img = get_the_post_thumbnail( $post, 'ryankitFeatImg' );
	if ( empty( $ryankit_img ) ) {
		$url = plugins_url( "src/bv-logo-white.svg", __FILE__ );
		$ryankit_img = '<img src="' . $url . '" alt="Binaryville Podcast Logo" />';
	}
	return $ryankit_img;
}

/**
 * Render the saved output from the dynamic block.
 * 
 * $attributes - array - Block attributes.
 * $content - Block inner content.
 */
function ryankit_dynamic_render_callback( $attributes, $content ) {

	global $post;

	// Get the latest posts using wp_get_recent_posts().
	$recent_posts = wp_get_recent_posts ( array(
		'category' => 2,
		'numberposts' => 1,
		'post_status' => 'publish',
	) );
	
	// Check if any posts were returned, if not, say so.
	if ( 0 === count( $recent_posts ) ) {
		return 'No posts.';
	}

	// Get the post ID for the first post returned.
	$post_id = $recent_posts[0]['ID'];
	
	// Get the post object based on post ID.
	$post = get_post( $post_id );

	// Setup postdata so regular template functions work.
	setup_postdata($post);

	// Reset postdata to avoid conflicts.
	wp_reset_postdata();
	
}