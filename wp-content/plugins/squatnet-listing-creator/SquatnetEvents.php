<?php
namespace SquatnetListingCreator;

class SquatnetEvents {
	// Register Custom Post Type
	public function __construct() {
		add_action( 'init', array( $this, 'registerType' ), 0 );
		add_action( 'admin_init', array( $this, 'adminInit' ) );
	}

	public function adminInit() {
		add_meta_box( 'event_meta', 'Event Information', array( $this, 'eventMeta' ), 'squatnet_event', 'normal', 'low' );
	}

	public function registerType() {
		$labels = array(
			'name'                  => _x( 'Event Requests', 'Post Type General Name', 'text_domain' ),
			'singular_name'         => _x( 'Event Request', 'Post Type Singular Name', 'text_domain' ),
			'menu_name'             => __( 'Event Request', 'text_domain' ),
			'name_admin_bar'        => __( 'Event Requests', 'text_domain' ),
			'archives'              => __( 'Item Archives', 'text_domain' ),
			'attributes'            => __( 'Item Attributes', 'text_domain' ),
			'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
			'all_items'             => __( 'All Items', 'text_domain' ),
			'add_new_item'          => __( 'Add New Item', 'text_domain' ),
			'add_new'               => __( 'Add New', 'text_domain' ),
			'new_item'              => __( 'New Item', 'text_domain' ),
			'edit_item'             => __( 'Edit Item', 'text_domain' ),
			'update_item'           => __( 'Update Item', 'text_domain' ),
			'view_item'             => __( 'View Item', 'text_domain' ),
			'view_items'            => __( 'View Items', 'text_domain' ),
			'search_items'          => __( 'Search Item', 'text_domain' ),
			'not_found'             => __( 'Not found', 'text_domain' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
			'featured_image'        => __( 'Featured Image', 'text_domain' ),
			'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
			'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
			'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
			'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
			'items_list'            => __( 'Items list', 'text_domain' ),
			'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
			'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
		);

		$args = array(
			'label'               => __( 'Event Submission', 'text_domain' ),
			'description'         => __( 'Events submitted to the site', 'text_domain' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-calendar',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
		);
		register_post_type( 'squatnet_event', $args );
	}

	public function eventMeta() {
		global $post;
		$custom = get_post_custom( $post->ID );

		$start    = isset( $custom['start'] ) ? $custom['start'][0] : null;
		$end      = isset( $custom['end'] ) ? $custom['end'][0] : null;
		$location = isset( $custom['location'] ) ? $custom['location'][0] : null;
		$price = isset( $custom['price'] ) ? $custom['price'][0] : null;
		// $flyer    = isset( $custom['flyer'] ) ? wp_get_attachment_url( $custom['flyer'][0] ) : null;
		$poster = isset( $custom['poster'] ) ? wp_get_attachment_url( $custom['poster'][0] ) : null;

		?>
	  <p>Date Start: <?php echo $start; ?></p>
	  <p>Date End: <?php echo $end; ?></p>
	  <p>Location: <?php echo $location; ?></p>
	  <p>Price: <?php echo $price; ?></p>
	  <p>Poster: <br/><?php echo $poster ? '<img width="200px" src="' . $poster . '" />' : null; ?></p>
		<?php
	}
}
