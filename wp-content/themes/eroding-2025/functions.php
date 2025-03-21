<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_separate', trailingslashit( get_stylesheet_directory_uri() ) . 'ctc-style.css', array( 'twentytwentyfive-style','twentytwentyfive-style','maincss','homecss' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 9223372036854775807 );

// END ENQUEUE PARENT ACTION


// STYLESHEETS
if ( !function_exists( 'main_css' ) ):
    function main_css() {
        wp_enqueue_style( 'maincss', trailingslashit( get_stylesheet_directory_uri() ) . 'main.css', array(  ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'main_css', 10 );

if ( !function_exists( 'home_css' ) ):
    function home_css() {
		if ( is_page( 'eroding-empire' ) ) {
        wp_enqueue_style( 'homecss', trailingslashit( get_stylesheet_directory_uri() ) . 'home.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'home_css', 10 );

if ( !function_exists( 'calendar_css' ) ):
    function calendar_css() {
		if ( is_page( 'calendar' ) ) {
        wp_enqueue_style( 'calendarcss', trailingslashit( get_stylesheet_directory_uri() ) . 'calendar.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'calendar_css', 10 );

if ( !function_exists( 'contact_css' ) ):
    function contact_css() {
		if ( is_page( 'contact' ) ) {
        wp_enqueue_style( 'contactcss', trailingslashit( get_stylesheet_directory_uri() ) . 'contact.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'contact_css', 10 );

if ( !function_exists( 'submit_css' ) ):
    function submit_css() {
		if ( is_page( 'submit' ) ) {
        wp_enqueue_style( 'submitcss', trailingslashit( get_stylesheet_directory_uri() ) . 'submit.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'submit_css', 10 );

if ( !function_exists( 'locations_css' ) ):
    function locations_css() {
		if ( is_page( 'locations' ) ) {
        wp_enqueue_style( 'locationscss', trailingslashit( get_stylesheet_directory_uri() ) . 'locations.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'locations_css', 10 );

if ( !function_exists( 'social_centres_css' ) ):
    function social_centres_css() {
		if ( is_post_type_archive( 'social-centre' ) ) {
        wp_enqueue_style( 'social_centrescss', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres.css', array(  ) );
        wp_enqueue_style( 'social_centres_archives_css', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres-archives.css', array(  ) );
		};
		if ( is_singular( 'social-centre' ) ) {
        wp_enqueue_style( 'social_centrescss', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres.css', array(  ) );	
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'social_centres_css', 10 );

if ( !function_exists( 'squats_css' ) ):
    function squats_css() {
		if ( is_post_type_archive( 'squat' ) ) {
        wp_enqueue_style( 'squatscss', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres.css', array(  ) );
        wp_enqueue_style( 'squats_archives_css', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres-archives.css', array(  ) );
		};
		if ( is_singular( 'squat' ) ) {
        wp_enqueue_style( 'squatscss', trailingslashit( get_stylesheet_directory_uri() ) . 'social-centres.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'squats_css', 10 );

if ( !function_exists( 'more_css' ) ):
    function more_css() {
		if ( is_page( 'more' ) ) {
        wp_enqueue_style( 'morecss', trailingslashit( get_stylesheet_directory_uri() ) . 'more.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'more_css', 10 );

if ( !function_exists( 'zines_css' ) ):
    function zines_css() {
		if ( is_post_type_archive( 'zinedistro' ) ) {
        wp_enqueue_style( 'zinescss', trailingslashit( get_stylesheet_directory_uri() ) . 'zines.css', array(  ) );
        wp_enqueue_style( 'zines_archives_css', trailingslashit( get_stylesheet_directory_uri() ) . 'zines-archives.css', array(  ) );
		};
		if ( is_singular( 'zinedistro' ) ) {
        wp_enqueue_style( 'zinescss', trailingslashit( get_stylesheet_directory_uri() ) . 'zines.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'zines_css', 10 );

if ( !function_exists( 'archives_css' ) ):
    function archives_css() {
		if ( is_page( ['archives','south-london-scum','scumfest'] ) ) {
        wp_enqueue_style( 'archivescss', trailingslashit( get_stylesheet_directory_uri() ) . 'archives.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'archives_css', 10 );

if ( !function_exists( 'archives_website_css' ) ):
    function archives_website_css() {
		if ( is_page( 'website' ) ) {
        wp_enqueue_style( 'archives-websitecss', trailingslashit( get_stylesheet_directory_uri() ) . 'archives-website.css', array(  ) );
		}
    }
endif;
add_action( 'wp_enqueue_scripts', 'archives_website_css', 10 );

