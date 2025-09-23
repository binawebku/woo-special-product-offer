<?php
/**
 * Admin settings for Woo Special Product Offer.
 *
 * @package Woo_Special_Product_Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WSPO_Settings' ) ) {

    /**
     * Registers settings and admin UI.
     */
    class WSPO_Settings {

        /**
         * Singleton instance.
         *
         * @var WSPO_Settings|null
         */
        protected static $instance = null;

        /**
         * Retrieve the settings instance.
         *
         * @return WSPO_Settings
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Hook registration.
         */
        protected function __construct() {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
        }

        /**
         * Add the plugin menu item under WooCommerce.
         */
        public function register_menu() {
            add_submenu_page(
                'woocommerce',
                __( 'Special Product Offer', 'woo-special-product-offer' ),
                __( 'Special Offer', 'woo-special-product-offer' ),
                'manage_woocommerce',
                'wspo-settings',
                array( $this, 'render_settings_page' )
            );
        }

        /**
         * Register plugin settings and fields.
         */
        public function register_settings() {
            register_setting( 'wspo_settings_group', 'wspo_settings', array( $this, 'sanitize_settings' ) );

            add_settings_section(
                'wspo_general_section',
                __( 'Purchase Options', 'woo-special-product-offer' ),
                array( $this, 'render_settings_description' ),
                'wspo-settings'
            );

            add_settings_field(
                'wspo_enable_subscription',
                __( 'Enable subscription offers', 'woo-special-product-offer' ),
                array( $this, 'render_enable_subscription_field' ),
                'wspo-settings',
                'wspo_general_section'
            );

            add_settings_field(
                'wspo_subscription_discount',
                __( 'Subscription discount (%)', 'woo-special-product-offer' ),
                array( $this, 'render_subscription_discount_field' ),
                'wspo-settings',
                'wspo_general_section'
            );

            add_settings_field(
                'wspo_frequencies',
                __( 'Subscription frequencies', 'woo-special-product-offer' ),
                array( $this, 'render_frequencies_field' ),
                'wspo-settings',
                'wspo_general_section'
            );
        }

        /**
         * Sanitize settings prior to save.
         *
         * @param array $input Raw settings.
         * @return array
         */
        public function sanitize_settings( $input ) {
            $defaults = WSPO_Plugin::get_default_settings();
            $output   = wp_parse_args( is_array( $input ) ? $input : array(), $defaults );

            $output['enable_subscription'] = empty( $input['enable_subscription'] ) ? 0 : 1;

            $discount = isset( $input['subscription_discount'] ) ? floatval( $input['subscription_discount'] ) : $defaults['subscription_discount'];
            $discount = max( 0, min( 100, $discount ) );
            $output['subscription_discount'] = $discount;

            $frequencies               = isset( $input['frequencies'] ) ? wp_unslash( $input['frequencies'] ) : array();
            $output['frequencies']     = WSPO_Plugin::sanitize_frequencies( $frequencies );

            return $output;
        }

        /**
         * Section description callback.
         */
        public function render_settings_description() {
            echo '<p>' . esc_html__( 'Configure how the Purchase Options panel behaves on product pages.', 'woo-special-product-offer' ) . '</p>';
        }

        /**
         * Render enable subscription checkbox.
         */
        public function render_enable_subscription_field() {
            $settings = WSPO_Plugin::get_settings();
            ?>
            <label for="wspo_enable_subscription">
                <input type="checkbox" name="wspo_settings[enable_subscription]" id="wspo_enable_subscription" value="1" <?php checked( $settings['enable_subscription'], 1 ); ?> />
                <?php esc_html_e( 'Allow customers to select a subscription and receive discounted pricing.', 'woo-special-product-offer' ); ?>
            </label>
            <?php
        }

        /**
         * Render subscription discount field.
         */
        public function render_subscription_discount_field() {
            $settings = WSPO_Plugin::get_settings();
            ?>
            <input type="number" min="0" max="100" step="0.1" name="wspo_settings[subscription_discount]" id="wspo_subscription_discount" value="<?php echo esc_attr( $settings['subscription_discount'] ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Percentage discount to apply when a shopper selects a subscription.', 'woo-special-product-offer' ); ?></p>
            <?php
        }

        /**
         * Render frequencies textarea.
         */
        public function render_frequencies_field() {
            $settings        = WSPO_Plugin::get_settings();
            $frequencies     = isset( $settings['frequencies'] ) ? (array) $settings['frequencies'] : array();
            $frequencies_txt = implode( "\n", $frequencies );
            ?>
            <textarea name="wspo_settings[frequencies]" id="wspo_frequencies" rows="5" cols="50" class="large-text code"><?php echo esc_textarea( $frequencies_txt ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Enter one frequency label per line (e.g. "Every Week"). These values will appear in the product dropdown.', 'woo-special-product-offer' ); ?></p>
            <?php
        }

        /**
         * Render the full settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }
            ?>
            <div class="wrap ws-special-offer-settings">
                <h1><?php esc_html_e( 'Woo Special Product Offer', 'woo-special-product-offer' ); ?></h1>
                <p class="description"><?php esc_html_e( 'Create tailored purchase journeys with lightweight controls that match your brand.', 'woo-special-product-offer' ); ?></p>
                <?php settings_errors( 'wspo_settings' ); ?>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'wspo_settings_group' );
                    do_settings_sections( 'wspo-settings' );
                    submit_button( __( 'Save changes', 'woo-special-product-offer' ) );
                    ?>
                </form>
            </div>
            <?php
        }
    }
}
