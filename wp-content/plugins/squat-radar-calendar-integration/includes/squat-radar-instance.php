<?php
/**
 * Manage the Squat Radar plugin.
 *
 * @package squat-radar
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Singleton for managing Squat Radar.
 */
class Squat_Radar_Instance {

	private static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		return null == self::$instance ? self::$instance = new self : self::$instance;
	}

	private function __construct() {
		include SQUAT_RADAR_DIR . 'includes/squat-radar-widget.php';
		include SQUAT_RADAR_DIR . 'includes/squat-radar-connector.php';
		include SQUAT_RADAR_DIR . 'includes/squat-radar-formatter.php';

		add_shortcode( 'squat_radar_sidebar', [$this, 'print_sidebar'] );
		add_action( 'plugins_loaded', [$this, 'i18n'], 5 );
		add_action( 'widgets_init', [ $this, 'add_sidebar'], 20 );
		add_action( 'widgets_init', ['Squat_Radar_Widget', 'register_widget'] );

		Squat_Radar_Formatter::register();
	}

	/**
	 * Load translation files.
	 */
	function i18n() {
		load_plugin_textdomain( 'squat-radar', false, '/languages' );
	}

	/**
	 * Shortcode callback to print the dynamic sidebar.
	 */
	function print_sidebar() {
		ob_start();

		if (is_active_sidebar('squat_radar_widget_shortcode')) {
			dynamic_sidebar('squat_radar_widget_shortcode');
		}

		return ob_get_clean();
	}

	/**
	 * Action callback to add the dynamic sidebar.
	 */
	function add_sidebar() {

		register_sidebar([
			'name'          => __( 'Squat Radar Shortcodes'),
			'description'=> __( 'This widget area is not by default displayed on frontend. It can be displayed with all its widgets with the [squat_radar_sidebar] shortcode.', 'squat-radar' ),
			'id'            => 'squat_radar_widget_shortcode',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		]);

	}

} 
