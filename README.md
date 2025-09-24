# Woo Special Product Offer

**Version:** 1.2.3  \
**Author:** [Wan Mohd Aiman Binawebpro.com]

Woo Special Product Offer is a lightweight WordPress plugin that enriches WooCommerce product pages with a modern “Purchase Options” experience. Customers can easily toggle between one-time purchases and subscriptions, choose their preferred delivery frequency, and instantly understand how much they will save. Behind the scenes the plugin keeps the cart, checkout, and resulting orders in sync with each shopper’s selection.

## Highlights

- Streamlined purchase selector injected directly into WooCommerce single product forms.
- Customisable discount engine for subscription purchases with flexible frequency options.
- Cart, checkout, and order meta automatically display the customer’s choices.
- Admin controls tucked neatly under **WooCommerce → Special Offer**.
- Dedicated **WSPO Subscribers** admin screen (and matching admin bar shortcut) to review and export subscriber contact details.
- Crafted with clean, modern code that stays out of the way of your storefront styling.

## Requirements

- WordPress 6.0 or newer
- WooCommerce 7.0 or newer
- PHP 7.4+

## Installation

1. Copy the plugin directory into your WooCommerce project at `wp-content/plugins/woo-special-product-offer/`.
2. Log into your WordPress admin dashboard and navigate to **Plugins → Installed Plugins**.
3. Activate **Woo Special Product Offer**.
4. During activation the plugin seeds sensible defaults (subscription enabled, 10% discount, weekly/monthly frequencies).

## Configuration

After activation visit **WooCommerce → Special Offer** to tailor the behaviour:

- **Enable subscription offers** – toggle availability of the subscription option on product pages.
- **Subscription discount (%)** – percentage discount applied to subscription purchases. The value is clamped between 0 and 100 and drives the automatic price adjustments in the cart.
- **Subscription frequencies** – define one frequency label per line (for example “Every Week”). Each label becomes an option in the product page dropdown and is saved with cart items and orders.

All settings are stored as a single option (`wspo_settings`) making backup and deployment straightforward. Changes take effect immediately on the storefront.

## Frontend behaviour

- The plugin enqueues a minimal CSS/JS bundle only on single-product pages (`is_product()`).
- The “Purchase Options” panel is injected before the default add-to-cart button using WooCommerce hooks.
- JavaScript enhances the UI with card highlighting, progressive disclosure of the frequency select box, and contextual messaging about discounts.
- The PHP template lives at `templates/purchase-options.php`. Developers can override the heading/helper text through the `wspo_purchase_options_heading` and `wspo_purchase_options_helper_text` filters.

## Pricing and persistence

- Customer selections are sanitised and stored inside cart item data with a base price snapshot.
- Subscription items receive the configured percentage discount during `woocommerce_before_calculate_totals` and whenever carts are restored from the session.
- Cart, checkout, and order summaries automatically surface the purchase type, chosen frequency, and savings so both the shopper and fulfilment teams stay informed.
- Subscriber billing phone numbers are copied into the `wspo_subscription_phone` order meta key, visible from the **Custom Fields** panel when viewing an order in the WooCommerce admin.

## Subscriber reporting

- Access the dedicated report via the **WSPO Subscribers** top-level menu in the WordPress dashboard or the matching shortcut in the admin bar.
- The report queries WooCommerce orders that captured the `wspo_subscription_phone` meta value and displays the order ID, customer name/email, phone number, and order date in a sortable table.
- Use the **Export CSV** button to trigger an authenticated download powered by the `admin_post_wspo_export_subscribers` action so you can share subscriber details with marketing or CRM tools.

## Usage tips

- Pair the plugin with WooCommerce Subscriptions or a custom recurring billing flow to complete the subscription experience.
- To completely disable subscriptions simply uncheck the setting—customers will only see the one-time purchase option while the UI stays consistent.
- Use WordPress translation tools (e.g. [Loco Translate](https://wordpress.org/plugins/loco-translate/)) to localise the text strings bundled with the plugin.

## Development

The codebase follows WordPress best practices with namespaced-style class prefixes (`WSPO_`). Assets are versioned alongside the plugin (`WSPO_VERSION`) for proper cache-busting. Feel free to extend behaviour via WordPress hooks or by copying the provided template into your theme and customising it with the standard WooCommerce template hierarchy.

If you improve or redistribute the plugin, please retain attribution to **[Wan Mohd Aiman Binawebpro.com]**.
