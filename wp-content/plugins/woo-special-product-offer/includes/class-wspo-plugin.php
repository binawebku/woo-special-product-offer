<?php
/**
 * Main plugin bootstrapper.
 *
 * @package Woo_Special_Product_Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WSPO_Plugin' ) ) {

    /**
     * Primary plugin controller.
     */
    class WSPO_Plugin {

        /**
         * Singleton instance.
         *
         * @var WSPO_Plugin|null
         */
        protected static $instance = null;

        /**
         * Retrieve the plugin instance.
         *
         * @return WSPO_Plugin
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Register required hooks.
         */
        protected function __construct() {
            $this->load_dependencies();
            $this->init_hooks();
        }

        /**
         * Load supporting class files.
         */
        protected function load_dependencies() {
            require_once WSPO_PLUGIN_DIR . 'includes/class-wspo-frontend.php';

            if ( is_admin() ) {
                require_once WSPO_PLUGIN_DIR . 'includes/class-wspo-settings.php';
                require_once WSPO_PLUGIN_DIR . 'includes/class-wspo-subscribers.php';
            }
        }

        /**
         * Initialise WordPress hooks.
         */
        protected function init_hooks() {
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

            if ( is_admin() ) {
                WSPO_Settings::instance();
                WSPO_Subscribers::instance();
            }

            WSPO_Frontend::instance();
        }

        /**
         * Handle plugin activation.
         */
        public static function activate() {
            $defaults = self::get_default_settings();
            $stored   = get_option( 'wspo_settings', array() );

            if ( ! is_array( $stored ) || empty( $stored ) ) {
                update_option( 'wspo_settings', $defaults );
                return;
            }

            $sanitized               = wp_parse_args( $stored, $defaults );
            $sanitized['frequencies'] = self::sanitize_frequencies( $sanitized['frequencies'] );

            update_option( 'wspo_settings', $sanitized );
        }

        /**
         * Handle plugin deactivation.
         */
        public static function deactivate() {
            // No deactivation tasks required.
        }

        /**
         * Load the plugin text domain for translations.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'woo-special-product-offer', false, dirname( plugin_basename( WSPO_PLUGIN_FILE ) ) . '/languages' );
        }

        /**
         * Retrieve plugin defaults.
         *
         * @return array
         */
        public static function get_default_settings() {
            return array(
                'enable_subscription'   => 1,
                'subscription_discount' => 10,
                'frequencies'           => array(
                    'Every Week',
                    'Every Month',
                ),
            );
        }

        /**
         * Fetch plugin settings merged with defaults.
         *
         * @return array
         */
        public static function get_settings() {
            $defaults = self::get_default_settings();
            $stored   = get_option( 'wspo_settings', array() );

            if ( ! is_array( $stored ) ) {
                $stored = array();
            }

            $settings = wp_parse_args( $stored, $defaults );

            $settings['enable_subscription']   = empty( $settings['enable_subscription'] ) ? 0 : 1;
            $settings['subscription_discount'] = isset( $settings['subscription_discount'] ) ? floatval( $settings['subscription_discount'] ) : 0;
            $settings['frequencies']           = self::sanitize_frequencies( $settings['frequencies'] );

            return $settings;
        }

        /**
         * Sanitize frequency values from settings.
         *
         * @param mixed $frequencies Frequency settings value.
         * @return array
         */
        public static function sanitize_frequencies( $frequencies ) {
            if ( is_string( $frequencies ) ) {
                $frequencies = preg_split( '/\r?\n/', $frequencies );
            }

            if ( ! is_array( $frequencies ) ) {
                $frequencies = array();
            }

            $sanitized = array();

            foreach ( $frequencies as $frequency ) {
                if ( is_array( $frequency ) && isset( $frequency['label'] ) ) {
                    $frequency = $frequency['label'];
                }

                $label = sanitize_text_field( wp_unslash( $frequency ) );

                if ( '' === $label ) {
                    continue;
                }

                $sanitized[] = $label;
            }

            return $sanitized;
        }

        /**
         * Produce the configured frequency options with machine-safe keys.
         *
         * @return array
         */
        public static function get_frequency_options() {
            $settings = self::get_settings();
            $options  = array();

            foreach ( $settings['frequencies'] as $label ) {
                $value = sanitize_title( $label );

                if ( '' === $value ) {
                    $value = substr( md5( $label ), 0, 8 );
                }

                $options[ $value ] = $label;
            }

            return $options;
        }

        /**
         * Load a template file within the plugin.
         *
         * @param string $template Template filename.
         * @param array  $args     Variables to expose in the template scope.
         */
        public static function get_template( $template, $args = array() ) {
            $template_file = trailingslashit( WSPO_PLUGIN_DIR . 'templates' ) . $template;

            if ( ! file_exists( $template_file ) ) {
                return;
            }

            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            }

            include $template_file;
        }
    }
}
