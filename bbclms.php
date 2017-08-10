<?php
/*
 * Plugin Name: BBCLMS Customization
 * Description: BBC LearnDash LMS customization code with CRON CSV Batch Import function. Credits to Delicious Brains Inc., https://github.com/A5hleyRich/wp-background-processing 
 * Author: Slobodan Brbaklic
 * Author Email: brbaso@gmail.com
 * Author URI: http://brbaso.com
 */
 
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * ========================================================================
 * CONSTANTS
 * ========================================================================
 */

// Directory
if (!defined( 'BBCLMS_CUSTOMIZATION_PLUGIN_DIR' ) ) {
	define( 'BBCLMS_CUSTOMIZATION_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url
if (!defined( 'BBCLMS_CUSTOMIZATION_PLUGIN_URL' ) ) {
	$plugin_url = plugin_dir_url( __FILE__ );

// If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
if ( is_ssl() )
	$plugin_url = str_replace( 'http://', 'https://', $plugin_url );
	define( 'BBCLMS_CUSTOMIZATION_PLUGIN_URL', $plugin_url );
}

// File
if (!defined( 'BBCLMS_CUSTOMIZATION_PLUGIN_FILE' ) ) {
	define( 'BBCLMS_CUSTOMIZATION_PLUGIN_FILE', __FILE__ );
}

/**
 * Main
 *
 * @return void
 */
function BBCLMS_CUSTOMIZATION_init(){
	global $bp, $BBCLMS_CUSTOMIZATION;

	//Check Learndash Plugin install and active
	if (  ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'bp_is_active' ) ) {
		add_action( 'admin_notices', 'bbclms_install_notice' );
		return;
	}   

	$main_include  = BBCLMS_CUSTOMIZATION_PLUGIN_DIR  . 'inc/BbclmsMainClass.php';

	try {
		if ( file_exists( $main_include ) ) {
			require( $main_include );
		} else{
			$msg = sprintf( __( "Couldn't load main class at:<br/>%s", 'bbclms' ), $main_include );
			throw new Exception( $msg, 404 );
		}
	} catch( Exception $e ) {
		$msg = sprintf( __( "<h1>Fatal error:</h1><hr/><pre>%s</pre>", 'bbclms' ), $e->getMessage() );
		echo $msg;
	}

	$BBCLMS_CUSTOMIZATION = BbclmsMainClass::instance();
	
}
add_action( 'plugins_loaded', 'BBCLMS_CUSTOMIZATION_init' );

/**
 * Must be called after hook 'plugins_loaded'
 *
 * useful - throughout the site e.g. functions can be called like: bbclms_custom() -> functions -> some_function_defined_in_BbclmsFunctions_class() ....
 */
function bbclms_custom() {
	global $BBCLMS_CUSTOMIZATION;
	return $BBCLMS_CUSTOMIZATION;
}
 
/**
 * Show the admin notice to install/activate LearnDash and BuddyPress first
 */
function bbclms_install_notice() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>BBCLMS Customization </strong> requires the LearnDash and BudyPress plugins to work !', 'bbclms');
    echo '</p></div>';
}
?>