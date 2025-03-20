<?php
/*
Plugin Name: Squatnet Listing Creator
Description: Propose simplified events for your Squatnet group directly from a WordPress site.
Version: 0.1.7
*/

namespace SquatnetListingCreator;

require 'vendor/autoload.php';
require_once 'Autoloader.php';

use Dotenv;
use Tracy\Debugger;
use Nette\Forms\Form;

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ );
$dotenv->safeLoad();

if ( isset( $_ENV['SQUATNET_DEBUG'] ) && $_ENV['SQUATNET_DEBUG'] === '1' ) {
	Debugger::enable( Debugger::DEVELOPMENT );
	Debugger::$showBar = true;
}

define( 'SQUATNET_WP_LC_NS', 'SquatnetListingCreator' );

class SquatnetListingCreator {
	private EventForm $form;
	private RadarConnector $radar;
	private Config $config;
	private Locations $locations;

	public function __construct() {
		// add_filter(
		// 	'mb_settings_pages',
		// 	array($this, 'addCustomFields')
		// );
		$this->config    = new Config();
		$this->radar     = new RadarConnector( $this->config );
		$this->locations = new Locations( $this->radar, $this->config );
		new OptionsPage( $this->config, $this->locations );
		new SquatnetEvents();
		$this->form = new EventForm( $this->locations, $this->radar );
		add_shortcode(
			'squatnet-listing-creator',
			array( $this, 'shortcode' )
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'addStyles' ) );
	}

	// Add Shortcode
	public function shortcode() {
		// $this->radar->authenticate();
		Form::initialize();
		return $this->form->render();
	}

	public function addCustomFields( $settings_pages ) {
		syslog( LOG_INFO, 'Settings page triggered' );
		$settings_pages[] = array(
			'id'          => 'squatnet-listing-creator',
			'option_name' => 'squatnet-listing-creator-settings',
			'menu_title'  => 'Squatnet Creator',
			'tabs'        => array(
				'settings' => 'Plugin Settings',
			),
		);

		return $settings_pages;
	}

	public function addStyles() {
		wp_enqueue_style( 'squatnet_form_creator', plugin_dir_url( __FILE__ ) . '/style.css' );
		wp_enqueue_style( 'squatnet_form_creator_dropdown', plugin_dir_url( __FILE__ ) . 'styles/choices.min.css' );
		wp_enqueue_script( 'squatnet_form_creator_dropdown_script', plugin_dir_url( __FILE__ ) . 'scripts/choices.min.js', array(), '1.0.0', true );
	}
}

new SquatnetListingCreator();
