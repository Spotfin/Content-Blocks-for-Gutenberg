<?php

/**
 * Plugin Name: Spotfin Content Blocks
 * Description: Spotfin Content Blocks with Gutenberg Support
 * Version: 1.0.1
 * Author: Spotfin Creative
 * License: GPL2
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Sfin_Gutenberg_Post_Content')) {
	/**
	 * Class Sfin_Gutenberg_Post_Content
	 */
	class Sfin_Gutenberg_Post_Content
	{
		/**
		 * Sfin_Gutenberg_Post_Content constructor.
		 */
		function __construct()
		{
			add_action('enqueue_block_editor_assets', array($this, 'sfin_load_the_block'));
			add_filter('register_post_type_args', array($this, 'sb_add_cpts_to_api'), 10, 2);
		}

		/**
		 * Load the block
		 */
		function sfin_load_the_block()
		{
			$required_js_files = array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
			);
			wp_enqueue_script(
				'sfin-gut-block',
				plugins_url('assets/js/sfin-gut-block.js', __FILE__),
				$required_js_files
			);

			wp_enqueue_style(
				'sfin-gut-block',
				plugins_url('assets/css/style.css', __FILE__),
				array('wp-edit-blocks')
			);
		}

		/**
		 * Show in REST API
		 *
		 * @param $args
		 * @param $post_type
		 *
		 * @return mixed
		 */
		function sb_add_cpts_to_api($args, $post_type)
		{
			if ('sfin-content-block' === $post_type) {
				$args['show_in_rest'] = true;
			}

			return $args;
		}
	}
}

new Sfin_Gutenberg_Post_Content();

/** 
 * Content Blocks
 * @class Sfin_Content_Blocks
 */

class Sfin_Content_Blocks
{
	protected static $instance;

	const POST_TYPE_SLUG = 'sfin-content-block';

	public static function init()
	{
		if (!isset(self::$instance))
			self::$instance = new Sfin_Content_Blocks();
		return self::$instance;
	}

	public static function getInstance()
	{
		return self::init();
	}

	public function __construct()
	{
		add_action('init', array($this, 'register'));

		if (is_admin()) {
			add_action('admin_head', array($this, 'admin_head'));
		}

		add_shortcode('sfin_content_block', array($this, 'shortcode_content_block'));
		add_shortcode('sfin_content_blocks', array($this, 'shortcode_content_blocks'));

		add_filter('sfin_content_blocks_content', 'wptexturize');
		add_filter('sfin_content_blocks_content', 'wpautop');
	}

	public function sanitize_view($path = false)
	{
		$return = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

		if ($path) {
			$path = trim($path, '\\/');
			if (substr($path, -4) !== '.php')
				$path .= '.php';
			$return .= $path;
		}
		if (!file_exists($return))
			return false;

		return $return;
	}

	private function render($path, $output = false, $local_vars = null)
	{
		$path = $this->sanitize_view($path);

		if (!$path)
			return false;

		$local_template = get_template_directory() . DIRECTORY_SEPARATOR . 'szbl' . DIRECTORY_SEPARATOR . basename($path);
		if (file_exists($local_template))
			$path = $local_template;

		if (is_array($local_vars) && count($local_vars) > 0)
			extract($local_vars);

		if ($output)
			include $path;

		ob_start();
		include $path;
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public function get_post_type_slug()
	{
		return apply_filters('sfin_content_blocks_slug', self::POST_TYPE_SLUG);
	}

	public function get_labels()
	{
		$labels = array(
			'name' => __('Content Blocks'),
			'singular_name' => __('Content Block'),
			'add_new' => __('Add New'),
			'add_new_item' => __('Add New Content Block'),
			'edit_item' => __('Edit Content Block'),
			'mew_item' => __('New Content Block'),
			'view_item' => __('View Content Block'),
			'search_items' => __('Search Content Blocks'),
			'not_found' => __('No content blocks found.'),
			'not_found_in_trash' => __('No content blocks found in trash.'),
		);
		return apply_filters('sfin_content_blocks-setting-labels', $labels);
	}

	public function register()
	{
		if (apply_filters('sfin_content_block-setting-post_thumbnails', true)) {
			add_theme_support('post-thumbnails');

			// use this action to add your image sizes
			do_action('sfin_content_blocks-setting-thumbnail_sizes');
		}

		$args = array(
			'labels' => $this->get_labels(),
			'description' => 'Reusable content for easy management and updating.',
			'public' => true,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_iu' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => true,
			'menu_icon' => 'dashicons-schedule',
			'menu_position' => 10,
			'capability_type' => 'post',
			'hierarchical' => true,
			'can_export' => true,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields', 'revisions'),
			'register_meta_box_cb' => array($this, 'add_meta_boxes'),
			'has_archive' => false,
			'rewrite' => array()
		);

		register_post_type($this->get_post_type_slug(), apply_filters('sfin_content_blocks-args', $args));
	}

	public function add_meta_boxes()
	{
		do_action('sfin_content_blocks_add_meta_boxes');
	}

	/* 
	 * Merges a set of terms (single, comma-separated or array)
	 * into a tax_query array
	 */
	private function merge_tax_query($terms, $tax_query, $taxonomy = 'sfin-content-tag', $term_field = 'slug', $operator = 'AND')
	{
		if (!is_array($terms))
			$terms = explode(',', $terms);

		$terms = array_map('trim', $terms);

		if (!is_array($tax_query))
			$tax_query = array();

		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field' => $term_field,
			'terms' => $terms,
			'operator' => $operator
		);

		return apply_filters('sfin_content_blocks-merge_tax_query', $tax_query);
	}

	/*
	 * Supports one or more Content Tags via non-core variable named "sfin_content_tags"
	 */
	public function get_content_blocks($args, $return_single = false)
	{
		$args = shortcode_atts(array(
			'posts_per_page' => get_query_var('posts_per_page') ? get_query_var('posts_per_page') : -1,
			'post_status' => 'publish',
			'post_parent' => null,
			'meta_query' => array(),
			'tax_query' => array(),
			'post__in' => '',
			'orderby' => 'menu_order',
			'order' => 'asc',
			'sfin_content_tags' => '',
			'sfin_content_tags_field' => 'slug',
			'sfin_content_tags_operator' => 'AND'
		), $args);

		$args['post_type'] = self::POST_TYPE_SLUG;

		if (empty($args['post__in']))
			unset($args['post__in']);
		elseif (!is_array($args['post__in']))
			$args['post__in'] = explode(',', $args['post__in']);

		if (!empty($args['sfin_content_tags']))
			$args['tax_query'] = $this->merge_tax_query($args['sfin_content_tags'], $args['tax_query'], 'sfin-content-tag', $args['sfin_content_tags_field'], $args['sfin_content_tags_operator']);

		unset($args['sfin_content_tags']);
		unset($args['sfin_content_tags_field']);
		unset($args['sfin_content_tags_operator']);

		apply_filters('sfin_content_blocks-get_content_blocks_args', $args);

		$posts = get_posts($args);

		if ($return_single)
			return apply_filters('sfin_get_content_blocks-get_post', $posts[0]);
		else
			return apply_filters('sfin_get_content_blocks-get_posts', $posts);
	}
}

Sfin_Content_Blocks::init();

function sfin_get_content_block($args = array())
{
	$args['posts_per_page'] = 1;
	return Sfin_Content_Blocks::init()->get_content_blocks($args, true);
}

function sfin_get_content_blocks($args = array())
{
	return Sfin_Content_Blocks::init()->get_content_blocks($args);
}
