<?php
namespace SquatnetListingCreator;

use SquatnetListingCreator\RadarConnector;
use SquatnetListingCreator\Config;

class Locations {

	private RadarConnector $radar;
	private Config $config;

	private $locations;
	private $localities;

	function __construct( RadarConnector $radar = null, Config $config = null ) {
		$this->radar  = $radar ? $radar : new RadarConnector();
		$this->config = $config ? $config : new Config();
		add_action( SQUATNET_WP_LC_NS . '_schedule_location_cache', array( $this, 'cacheLocations' ) );
		if ( ! wp_next_scheduled( SQUATNET_WP_LC_NS . '_schedule_location_cache' ) ) {
			wp_schedule_event( time(), 'daily', SQUATNET_WP_LC_NS . '_schedule_location_cache' );
		}
		register_deactivation_hook( __FILE__, SQUATNET_WP_LC_NS . '_schedule_location_cache' );
		$this->locations = $this->config->get( 'locations', true );
		if ( $this->locations === false ) {
			$this->cacheLocations();
			$this->locations = $this->config->get( 'locations', true );
		}
		$this->localities = $this->config->get( 'localities', true );
	}

	public function cacheLocations( $location_override = null ) {
		if ( ! $this->config->get( 'api_url' ) ) {
			return;
		}
		$location_search_path = 'search/location.json?fields=title,address,uuid';
		$response             = $this->radar->get( $location_search_path . '&limit=1' );
		$raw_locations        = array();
		$localities           = array();
		if ( $response->getStatusCode() === 200 ) {
			$raw_locations = json_decode( $response->getBody(), true );
			$localities    = $raw_locations['facets']['locality'];
		}
		if ( $this->config->get( 'geographic_area' ) || $location_override ) {
			$path     = $location_search_path
				. '&filter[~and][field_address:locality][~eq]='
				. ( $location_override ? $location_override : $this->config->get( 'geographic_area' ) );
			$response = $this->radar->get( $path );
			if ( $response->getStatusCode() === 200 ) {
				$raw_locations = json_decode( $response->getBody(), true );
			}
		}

		$this->config->createOrUpdateOption( SQUATNET_WP_LC_NS . '_locations', $raw_locations['result'] );

		$this->config->createOrUpdateOption( SQUATNET_WP_LC_NS . '_localities', $localities );

	}


	function deactivate() {
		$timestamp = wp_next_scheduled( array( $this, 'cacheLocations' ) );
		wp_unschedule_event( $timestamp, array( $this, 'cacheLocations' ) );
	}

	function getAll( string $location_type = '' ) {
		if ( $location_type === 'options' ) {
			return $this->localities;
		} else {
			return $this->locations;
		}
	}

	function getFiltered( $area ) {
		$filtered_locations = array();
		foreach ( $this->locations as $key => $location ) {
			if ( $area === $location['address']['locality'] ) {
				$filtered_locations[] = $location;
			}
		}
		return $filtered_locations;
	}

	function getFormLocations() {
		// return $this->getFiltered($this->config->get('geographic_area'));
		$options = array();
		if ( $this->locations ) {
			foreach ( $this->locations as $key => $location ) {
				$options[ $location['uuid'] ] = $location['title'];
			}
		}

		natsort( $options );

		return $options;
	}
}
