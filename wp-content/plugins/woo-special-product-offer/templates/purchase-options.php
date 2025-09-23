<?php
/**
 * Purchase options template.
 *
 * @package Woo_Special_Product_Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$discount          = isset( $settings['subscription_discount'] ) ? floatval( $settings['subscription_discount'] ) : 0;
$current_type      = isset( $_POST['wspo_purchase_type'] ) ? sanitize_key( wp_unslash( $_POST['wspo_purchase_type'] ) ) : 'one_time';
$current_frequency = isset( $_POST['wspo_plan_frequency'] ) ? sanitize_key( wp_unslash( $_POST['wspo_plan_frequency'] ) ) : '';

if ( ! in_array( $current_type, array( 'one_time', 'subscription' ), true ) ) {
    $current_type = 'one_time';
}

if ( 'subscription' !== $current_type ) {
    $current_frequency = '';
}

$frequency_keys = array_keys( (array) $frequencies );
if ( 'subscription' === $current_type && ( empty( $current_frequency ) || ! array_key_exists( $current_frequency, $frequencies ) ) ) {
    $current_frequency = reset( $frequency_keys );
}

$heading = apply_filters( 'wspo_purchase_options_heading', __( 'Purchase Options', 'woo-special-product-offer' ) );
$helper  = apply_filters( 'wspo_purchase_options_helper_text', __( 'Choose how you would like to buy this product.', 'woo-special-product-offer' ) );
?>
<div class="wspo-purchase-options" data-discount="<?php echo esc_attr( $discount ); ?>">
    <div class="wspo-header">
        <h3 class="wspo-title"><?php echo esc_html( $heading ); ?></h3>
        <p class="wspo-helper"><?php echo esc_html( $helper ); ?></p>
    </div>

    <p class="wspo-price-note" aria-live="polite"></p>

    <div class="wspo-option">
        <label class="wspo-option-card">
            <input type="radio" name="wspo_purchase_type" value="one_time" <?php checked( 'one_time', $current_type ); ?> />
            <span class="wspo-option-content">
                <span class="wspo-option-title"><?php esc_html_e( 'One-time purchase', 'woo-special-product-offer' ); ?></span>
                <span class="wspo-option-description"><?php esc_html_e( 'Pay the standard price today.', 'woo-special-product-offer' ); ?></span>
            </span>
        </label>
    </div>

    <?php if ( ! empty( $has_subscribe ) && ! empty( $frequencies ) ) : ?>
        <div class="wspo-option">
            <label class="wspo-option-card">
                <input type="radio" name="wspo_purchase_type" value="subscription" <?php checked( 'subscription', $current_type ); ?> />
                <span class="wspo-option-content">
                    <span class="wspo-option-title">
                        <?php esc_html_e( 'Subscribe & Save', 'woo-special-product-offer' ); ?>
                        <?php if ( $discount > 0 ) : ?>
                            <span class="wspo-badge"><?php printf( esc_html__( 'Save %s%%', 'woo-special-product-offer' ), esc_html( rtrim( rtrim( wc_format_decimal( $discount, 2 ), '0' ), '.' ) ) ); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="wspo-option-description"><?php esc_html_e( 'Auto-deliver on your schedule and unlock exclusive savings.', 'woo-special-product-offer' ); ?></span>
                </span>
            </label>

            <div class="wspo-frequency <?php echo 'subscription' === $current_type ? '' : 'wspo-hidden'; ?>">
                <label for="wspo_plan_frequency"><?php esc_html_e( 'Delivery frequency', 'woo-special-product-offer' ); ?></label>
                <select name="wspo_plan_frequency" id="wspo_plan_frequency">
                    <?php foreach ( $frequencies as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_frequency ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <?php wp_nonce_field( 'wspo_purchase_options', 'wspo_nonce' ); ?>
</div>
