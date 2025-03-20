<?php

/**
 * Squat Radar bootstrap file
 *
 * @link              https://radar.squat.net/
 * @since             2.0.0
 * @package           Squat_Radar
 *
 * @wordpress-plugin
 * Plugin Name:       Squat Radar calendar integration
 * Plugin URI:        https://0xacab.org/radar/radar-wp
 * Description:       Provides widget, and shortcode, integration for displaying events from https://radar.squat.net/ agenda.
 * Version:           2.0.9
 * Author:            Radar contributors
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       squat-radar
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'SQUAT_RADAR_URL', plugin_dir_url( __FILE__ ) );
define( 'SQUAT_RADAR_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
include SQUAT_RADAR_DIR . 'includes/squat-radar-instance.php';

Squat_Radar_Instance::get_instance();
