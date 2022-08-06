<?php
/**
 * This file is the functionality loader.
 * It will include plugin setting file and call the proper files based on the setting.
 *
 * @since 2.0.0
 * @package BNB\Loader
 */

namespace BNB\Loader;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Load BNB functionality 
 * 
 * @since 2.0.0
 */
class Loader {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->include();
		$this->hooks();
	}
	
	/**
	 * function to call all hooks
	 */
	public function hooks(){
		// create notifications CPT.
		add_action( 'init', array( $this, 'create_notification_cpt' ));
		
	}

	/**
	 * Include all the required files. Ex. Plugin settings
	 */
	private function include() {
		// include others files here.
	}

	/**
	 * ****************************************************************
	 ** Thoughts: What i need in settings?
	 *
	 * 1. Add a CPT( Notifications )
	 * 2. Setting to enable WordPress and Plugin specific notifications  
	 * ****************************************************************
	 */

	/**
	 * Register a custom post type called "Notifications".
	 *
	 * @see get_post_type_labels() for label keys.
	 */
	function create_notification_cpt() {
		$labels = array(
			'name'                  => _x( 'Notifications', 'Post type general name', 'buddy-notification-bellbuddy-notification-bell' ),
			'singular_name'         => _x( 'Notification', 'Post type singular name', 'buddy-notification-bellbuddy-notification-bell' ),
			'menu_name'             => _x( 'Notifications', 'Admin Menu text', 'buddy-notification-bellbuddy-notification-bell' ),
			'name_admin_bar'        => _x( 'Notification', 'Add New on Toolbar', 'buddy-notification-bellbuddy-notification-bell' ),
			'add_new'               => __( 'Add New', 'buddy-notification-bellbuddy-notification-bell' ),
			'add_new_item'          => __( 'Add New Notification', 'buddy-notification-bellbuddy-notification-bell' ),
			'new_item'              => __( 'New Notification', 'buddy-notification-bellbuddy-notification-bell' ),
			'edit_item'             => __( 'Edit Notification', 'buddy-notification-bellbuddy-notification-bell' ),
			'view_item'             => __( 'View Notification', 'buddy-notification-bellbuddy-notification-bell' ),
			'all_items'             => __( 'All Notifications', 'buddy-notification-bellbuddy-notification-bell' ),
			'search_items'          => __( 'Search Notifications', 'buddy-notification-bellbuddy-notification-bell' ),
			'parent_item_colon'     => __( 'Parent Notifications:', 'buddy-notification-bellbuddy-notification-bell' ),
			'not_found'             => __( 'No Notifications found.', 'buddy-notification-bellbuddy-notification-bell' ),
			'not_found_in_trash'    => __( 'No Notifications found in Trash.', 'buddy-notification-bellbuddy-notification-bell' ),
			'featured_image'        => _x( 'Notification Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'buddy-notification-bellbuddy-notification-bell' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'buddy-notification-bellbuddy-notification-bell' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'buddy-notification-bellbuddy-notification-bell' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'buddy-notification-bellbuddy-notification-bell' ),
			'archives'              => _x( 'Notification archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
			'insert_into_item'      => _x( 'Insert into Notification', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this Notification', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
			'filter_items_list'     => _x( 'Filter Notifications list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
			'items_list_navigation' => _x( 'Notifications list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
			'items_list'            => _x( 'Notifications list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'buddy-notification-bellbuddy-notification-bell' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'buddy_notification' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author' ),
			'menu_icon'          => BUDDY_NOTIFICATION_BELL_PLUGINS_URL.'/src/assets/images/notification.png',
		);
		register_post_type( 'buddy_notification', $args );
	}

}
$instance = new Loader();