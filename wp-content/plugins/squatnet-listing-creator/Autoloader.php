<?php

define( 'SQUATNET_CREATOR_CLASSES_BASE_PATH', realpath( dirname( __FILE__ ) ) );

function squatnet_event_creator_autoload( $class ) {
	$filename = SQUATNET_CREATOR_CLASSES_BASE_PATH . str_replace( '\\', '/', str_replace( 'SquatnetListingCreator', '', $class ) );

	if ( file_exists( $filename . '.php' ) ) {
		include( $filename . '.php' );
		if ( class_exists( $class ) ) {
			return true;
		}
	} elseif ( file_exists( $filename . '/index.php' ) ) {
		include( $filename . '/index.php' );
		if ( class_exists( $class ) ) {
			return true;
		}
	}
	return false;
}
spl_autoload_register( 'squatnet_event_creator_autoload' );
