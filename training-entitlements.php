<?php
/**
 * Plugin Name: Aspen Training Entitlements
 * Description: Converts paid WooCommerce line items into secure LifterLMS enrollment batches.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Aspen
 * Text Domain: aspen-training-entitlements
 */

defined( 'ABSPATH' ) || exit;

define( 'ATE_VERSION', '1.0.0' );
define( 'ATE_SCHEMA_VERSION', '1.0.0' );
define( 'ATE_FILE', __FILE__ );
define( 'ATE_DIR', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Aspen\\TrainingEntitlements\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = ATE_DIR . 'src/' . str_replace( array( $prefix, '\\' ), array( '', '/' ), $class ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Aspen\\TrainingEntitlements\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Aspen\\TrainingEntitlements\\Activator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Aspen\\TrainingEntitlements\\Plugin', 'boot' ), 20 );
