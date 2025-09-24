<?php
/**
 * Plugin Name:       Woo Special Product Offer
 * Plugin URI:        https://binawebpro.com/woo-special-product-offer
 * Description:       Adds modern purchase option controls to WooCommerce products so customers can choose between one-time purchases and subscriptions.
 * Version:           1.2.2
 * Author:            [Wan Mohd Aiman Binawebpro.com]
 * Author URI:        https://binawebpro.com
 * Text Domain:       woo-special-product-offer
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WSPO_VERSION', '1.2.2' );
define( 'WSPO_PLUGIN_FILE', __FILE__ );
define( 'WSPO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSPO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WSPO_PLUGIN_DIR . 'includes/class-wspo-plugin.php';

register_activation_hook( WSPO_PLUGIN_FILE, array( 'WSPO_Plugin', 'activate' ) );
register_deactivation_hook( WSPO_PLUGIN_FILE, array( 'WSPO_Plugin', 'deactivate' ) );

WSPO_Plugin::instance();
