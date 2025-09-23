<?php
/**
 * Frontend functionality for Woo Special Product Offer.
 *
 * @package Woo_Special_Product_Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WSPO_Frontend' ) ) {

    /**
     * Handles public hooks and rendering.
     */
    class WSPO_Frontend {

        /**
         * Singleton instance.
         *
         * @var WSPO_Frontend|null
         */
        protected static $instance = null;

        /**
         * Retrieve the frontend instance.
         *
         * @return WSPO_Frontend
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Hook into WordPress.
         */
        protected function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_purchase_options' ), 15 );
            add_filter( 'woocommerce_add_cart_item_data', array( $this, 'save_purchase_selection' ), 10, 3 );
            add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'restore_cart_item_data' ), 10, 3 );
            add_action( 'woocommerce_before_calculate_totals', array( $this, 'adjust_cart_item_price' ), 10 );
            add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
            add_action( 'woocommerce_checkout_create_order', array( $this, 'record_subscription_phone' ), 10, 2 );
            add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 4 );
        }

        /**
         * Register scripts and styles.
         */
        public function enqueue_assets() {
            if ( ! function_exists( 'is_product' ) || ! is_product() ) {
                return;
            }

            wp_enqueue_style(
                'wspo-frontend',
                WSPO_PLUGIN_URL . 'assets/css/woo-special-product-offer.css',
                array(),
                WSPO_VERSION
            );

            wp_enqueue_script(
                'wspo-frontend',
                WSPO_PLUGIN_URL . 'assets/js/woo-special-product-offer.js',
                array( 'jquery' ),
                WSPO_VERSION,
                true
            );

            $settings = WSPO_Plugin::get_settings();

            wp_localize_script(
                'wspo-frontend',
                'wspoPurchaseOptions',
                array(
                    'discount' => isset( $settings['subscription_discount'] ) ? floatval( $settings['subscription_discount'] ) : 0,
                    'strings'  => array(
                        'subscriptionTemplate'    => __( 'Save %s%% on every delivery. Discount applied at checkout.', 'woo-special-product-offer' ),
                        'subscriptionNoDiscount'  => __( 'Subscription selected. Pricing will update at checkout.', 'woo-special-product-offer' ),
                        'oneTime'                 => __( 'One-time purchase selected.', 'woo-special-product-offer' ),
                    ),
                )
            );
        }

        /**
         * Render purchase options on the product page.
         */
        public function render_purchase_options() {
            global $product;

            if ( ! class_exists( 'WC_Product' ) ) {
                return;
            }

            if ( ! $product instanceof WC_Product ) {
                return;
            }

            $settings    = WSPO_Plugin::get_settings();
            $frequencies = WSPO_Plugin::get_frequency_options();

            if ( empty( $settings['enable_subscription'] ) && empty( $frequencies ) ) {
                return;
            }

            WSPO_Plugin::get_template(
                'purchase-options.php',
                array(
                    'product'      => $product,
                    'settings'     => $settings,
                    'frequencies'  => $frequencies,
                    'has_subscribe' => ! empty( $settings['enable_subscription'] ) && ! empty( $frequencies ),
                )
            );
        }

        /**
         * Persist purchase selections to the cart.
         *
         * @param array $cart_item_data Existing cart item data.
         * @param int   $product_id     Product identifier.
         * @param int   $variation_id   Variation identifier.
         * @return array
         */
        public function save_purchase_selection( $cart_item_data, $product_id, $variation_id ) {
            if ( ! function_exists( 'wc_get_product' ) ) {
                return $cart_item_data;
            }

            $nonce = isset( $_POST['wspo_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wspo_nonce'] ) ) : '';

            if ( $nonce && ! wp_verify_nonce( $nonce, 'wspo_purchase_options' ) ) {
                return $cart_item_data;
            }

            $purchase_type = isset( $_POST['wspo_purchase_type'] ) ? sanitize_key( wp_unslash( $_POST['wspo_purchase_type'] ) ) : 'one_time';
            $frequency     = isset( $_POST['wspo_plan_frequency'] ) ? sanitize_key( wp_unslash( $_POST['wspo_plan_frequency'] ) ) : '';

            $valid_types = array( 'one_time', 'subscription' );
            if ( ! in_array( $purchase_type, $valid_types, true ) ) {
                $purchase_type = 'one_time';
            }

            $settings          = WSPO_Plugin::get_settings();
            $frequency_options = WSPO_Plugin::get_frequency_options();

            $frequency_label = '';

            if ( 'subscription' === $purchase_type ) {
                if ( empty( $frequency_options ) ) {
                    $purchase_type = 'one_time';
                    $frequency     = '';
                } else {
                    if ( empty( $frequency ) || ! array_key_exists( $frequency, $frequency_options ) ) {
                        $frequency_keys = array_keys( $frequency_options );
                        $frequency      = reset( $frequency_keys );
                    }

                    if ( $frequency && array_key_exists( $frequency, $frequency_options ) ) {
                        $frequency_label = $frequency_options[ $frequency ];
                    }
                }
            } else {
                $frequency = '';
            }

            $product_object = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
            $base_price     = $product_object instanceof WC_Product ? max( 0, (float) $product_object->get_price() ) : 0;

            $discount_value = isset( $settings['subscription_discount'] ) ? max( 0, min( 100, floatval( $settings['subscription_discount'] ) ) ) : 0;

            $wspo_data = array(
                'type'            => $purchase_type,
                'frequency'       => $frequency,
                'frequency_label' => $frequency_label,
                'discount'        => $discount_value,
                'base_price'      => $base_price,
            );

            $cart_item_data['wspo_data']    = $wspo_data;
            $cart_item_data['wspo_cart_id'] = md5( wp_json_encode( $wspo_data ) );

            return $cart_item_data;
        }

        /**
         * Restore the purchase options when loading cart data from the session.
         *
         * @param array $cart_item Cart item array.
         * @param array $values    Stored values.
         * @param string $cart_item_key Cart item key.
         * @return array
         */
        public function restore_cart_item_data( $cart_item, $values, $cart_item_key ) {
            if ( isset( $values['wspo_data'] ) ) {
                $cart_item['wspo_data'] = $values['wspo_data'];
            }

            if ( isset( $cart_item['wspo_data']['discount'] ) ) {
                $cart_item['wspo_data']['discount'] = max( 0, min( 100, floatval( $cart_item['wspo_data']['discount'] ) ) );
            }

            if ( class_exists( 'WC_Product' ) && isset( $cart_item['wspo_data']['base_price'] ) && isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
                $cart_item['wspo_data']['base_price'] = max( 0, (float) $cart_item['wspo_data']['base_price'] );
            }

            return $cart_item;
        }

        /**
         * Adjust prices in the cart for subscriptions.
         *
         * @param WC_Cart $cart WooCommerce cart object.
         */
        public function adjust_cart_item_price( $cart ) {
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                return;
            }

            if ( ! class_exists( 'WC_Cart' ) || ! $cart instanceof WC_Cart || empty( $cart->cart_contents ) ) {
                return;
            }

            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( empty( $cart_item['wspo_data'] ) || ! isset( $cart_item['data'] ) || ! class_exists( 'WC_Product' ) || ! $cart_item['data'] instanceof WC_Product ) {
                    continue;
                }

                $data = $cart_item['wspo_data'];

                if ( 'subscription' !== $data['type'] ) {
                    continue;
                }

                $base_price = isset( $data['base_price'] ) ? (float) $data['base_price'] : $cart_item['data']->get_price();
                $discount   = isset( $data['discount'] ) ? max( 0, min( 100, floatval( $data['discount'] ) ) ) : 0;
                $adjusted   = $base_price * ( 1 - ( $discount / 100 ) );

                if ( $adjusted < 0 ) {
                    $adjusted = 0;
                }

                $cart->cart_contents[ $cart_item_key ]['data']->set_price( $adjusted );
            }
        }

        /**
         * Display purchase selections on cart and checkout screens.
         *
         * @param array $item_data Display data array.
         * @param array $cart_item Cart item array.
         * @return array
         */
        public function display_cart_item_data( $item_data, $cart_item ) {
            if ( empty( $cart_item['wspo_data'] ) ) {
                return $item_data;
            }

            $data       = $cart_item['wspo_data'];
            $type_label = 'subscription' === $data['type'] ? __( 'Subscription', 'woo-special-product-offer' ) : __( 'One-time purchase', 'woo-special-product-offer' );

            $item_data[] = array(
                'name'  => __( 'Purchase Type', 'woo-special-product-offer' ),
                'value' => $type_label,
            );

            if ( 'subscription' === $data['type'] && ! empty( $data['frequency_label'] ) ) {
                $item_data[] = array(
                    'name'  => __( 'Delivery Frequency', 'woo-special-product-offer' ),
                    'value' => $data['frequency_label'],
                );
            }

            if ( 'subscription' === $data['type'] && ! empty( $data['discount'] ) ) {
                $discount_value = wc_format_decimal( floatval( $data['discount'] ), 2 );
                $discount_value = rtrim( rtrim( $discount_value, '0' ), '.' );

                $item_data[] = array(
                    'name'  => __( 'Subscription Savings', 'woo-special-product-offer' ),
                    'value' => sprintf( /* translators: %s: discount percentage. */ __( '%s%% discount', 'woo-special-product-offer' ), $discount_value ),
                );
            }

            return $item_data;
        }

        /**
         * Store the subscriber's phone number on the order when relevant.
         *
         * @param WC_Order $order Order instance.
         * @param array    $data  Checkout posted data.
         */
        public function record_subscription_phone( $order, $data ) {
            if ( ! class_exists( 'WC_Order' ) || ! $order instanceof WC_Order ) {
                return;
            }

            $cart = null;

            if ( function_exists( 'WC' ) ) {
                $cart = WC()->cart;
            }

            if ( ! class_exists( 'WC_Cart' ) || ! $cart instanceof WC_Cart ) {
                return;
            }

            $has_subscription = false;

            foreach ( $cart->get_cart() as $values ) {
                if ( empty( $values['wspo_data'] ) || empty( $values['wspo_data']['type'] ) ) {
                    continue;
                }

                if ( 'subscription' === $values['wspo_data']['type'] ) {
                    $has_subscription = true;
                    break;
                }
            }

            if ( ! $has_subscription ) {
                return;
            }

            $phone = '';

            if ( is_array( $data ) && ! empty( $data['billing_phone'] ) && ! is_array( $data['billing_phone'] ) ) {
                $phone = $data['billing_phone'];
            } elseif ( method_exists( $order, 'get_billing_phone' ) ) {
                $phone = $order->get_billing_phone();
            }

            if ( empty( $phone ) || is_array( $phone ) ) {
                return;
            }

            $phone = sanitize_text_field( wp_unslash( $phone ) );

            if ( '' === $phone ) {
                return;
            }

            $existing_phone = $order->get_meta( 'wspo_subscription_phone', true );

            if ( $existing_phone === $phone ) {
                return;
            }

            $order->update_meta_data( 'wspo_subscription_phone', $phone );
        }

        /**
         * Persist purchase selections to order line items.
         *
         * @param WC_Order_Item_Product $item          Order item instance.
         * @param string                $cart_item_key Cart item key.
         * @param array                 $values        Cart values.
         * @param WC_Order              $order         Order instance.
         */
        public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
            if ( empty( $values['wspo_data'] ) ) {
                return;
            }

            $data       = $values['wspo_data'];
            $type_label = 'subscription' === $data['type'] ? __( 'Subscription', 'woo-special-product-offer' ) : __( 'One-time purchase', 'woo-special-product-offer' );

            $item->add_meta_data( __( 'Purchase Type', 'woo-special-product-offer' ), $type_label, true );

            if ( 'subscription' === $data['type'] && ! empty( $data['frequency_label'] ) ) {
                $item->add_meta_data( __( 'Delivery Frequency', 'woo-special-product-offer' ), $data['frequency_label'], true );
            }

            if ( 'subscription' === $data['type'] && ! empty( $data['discount'] ) ) {
                $discount_value = wc_format_decimal( floatval( $data['discount'] ), 2 );
                $discount_value = rtrim( rtrim( $discount_value, '0' ), '.' );

                $item->add_meta_data(
                    __( 'Subscription Savings', 'woo-special-product-offer' ),
                    sprintf( /* translators: %s: discount percentage. */ __( '%s%% discount', 'woo-special-product-offer' ), $discount_value ),
                    true
                );
            }
        }
    }
}
