<?php

namespace SquatnetListingCreator;

class Config {


	private $settings;

	public function __construct() {
		$this->settings = get_option( SQUATNET_WP_LC_NS . '_settings' );
	}

	public function debug(): void {
		echo '<pre>';
		print_r( $this->settings );
		echo '</pre>';
	}

	public function get( string $setting, bool $is_option = false ) {
		if ( $is_option ) {
			return get_option( SQUATNET_WP_LC_NS . '_' . $setting );
		} elseif ( isset( $this->settings ) && isset( $this->settings[ $setting ] ) ) {
			return $this->settings[ $setting ];
		}

		return null;
	}

	public function createOrUpdateOption( string $option_name, $option_value ) {
		$option_function = 'update_option';
		if ( get_option( $option_name ) === false ) {
			$option_function = 'add_option';
		}

		call_user_func_array(
			$option_function,
			array( $option_name, $option_value )
		);
	}
}
