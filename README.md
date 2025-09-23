# Woo Special Product Offer

**Version:** 1.1.0  
**Author:** [Wan Mohd Aiman Binawebpro.com]

Woo Special Product Offer is a lightweight WooCommerce extension that creates contextual upsell and cross-sell experiences across your storefront. It enables store owners to present targeted promotional bundles, time-limited discounts, and free gift incentives directly within the product page, cart, and checkout flows—helping you increase the average order value without sacrificing performance.

## Features
- Display dynamic promotional banners on single product pages based on customer behavior or cart contents.
- Offer tiered discounts or free gifts when shoppers add eligible product combinations to their cart.
- Provide clear analytics for each offer so that you can refine campaigns and drive higher conversions.
- Use shortcodes or hooks to embed the offer widgets anywhere in your WooCommerce theme.

## Requirements & Dependencies
- PHP 7.4 or higher
- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- (Optional) A/B testing plugin if you want to compare offer performance across multiple variations

## Installation
1. Download the plugin source and compress it into a `.zip` archive (or clone the repository into your `wp-content/plugins` directory).
2. In your WordPress dashboard, navigate to **Plugins → Add New → Upload Plugin** and upload the archive.
3. Activate **Woo Special Product Offer** from the **Plugins** screen.
4. Confirm that WooCommerce is active and visit **WooCommerce → Settings → Special Offers** to begin configuring campaigns.

## Configuration Guide
1. Go to **WooCommerce → Settings → Special Offers**.
2. Click **Add Offer** to create a new promotional rule.
3. Complete the following fields:
   - **Offer Name:** Internal label for the promotion.
   - **Trigger Products:** Select products or categories that must be in the cart before the offer becomes visible.
   - **Reward Type:** Choose between percentage discount, fixed discount, or free gift.
   - **Reward Amount/Product:** Define the discount amount or the product to gift.
   - **Display Location:** Select where the offer should appear (Product Page, Cart, Checkout, or Custom Hook).
   - **Schedule:** Optionally set start and end dates or limit to specific days/hours.
4. Save the offer. It will begin displaying immediately based on its schedule and trigger conditions.

### Purchase Options
- **Upsell Bundles:** Encourage upgrades by pairing higher-tier products with relevant accessories.
- **Cross-Sells:** Suggest complementary items when shoppers add base products to the cart.
- **BOGO / Free Gift:** Reward customers when they add a set number of items or reach a minimum cart subtotal.
- **Time-Limited Promotions:** Configure countdown timers to drive urgency during campaigns.

## Shortcodes & Hooks
Use these helpers to embed offers throughout your theme or custom templates:

| Type | Handle | Description |
|------|--------|-------------|
| Shortcode | `[woo_special_offer]` | Renders the default promotional widget based on the current context (product page or cart). |
| Shortcode | `[woo_special_offer id="123"]` | Displays the offer with ID 123 regardless of context. Useful for landing pages. |
| Action Hook | `do_action( 'woo_special_offer_render', $offer_id );` | Programmatically render a specific offer in your templates. Pass `null` for `$offer_id` to load the best matching offer automatically. |
| Filter Hook | `apply_filters( 'woo_special_offer_conditions', $conditions, $offer_id );` | Adjust offer conditions before evaluation to integrate with membership or loyalty plugins. |

## Testing Instructions
1. **Local Environment:** Spin up a WordPress development environment (e.g., using Local WP, DevKinsta, or Docker) with WooCommerce enabled.
2. **Plugin Activation:** Activate the plugin and ensure no PHP warnings or notices appear in the debug log.
3. **Functional Verification:**
   - Create at least one upsell bundle and ensure it appears on the targeted product page.
   - Add matching products to the cart and confirm the configured discount or gift is applied.
   - Validate that removing the trigger product hides the offer and removes associated rewards.
4. **Shortcode Rendering:** Place `[woo_special_offer]` in a page or widget area and confirm the widget loads with expected styling and content.
5. **Hook Integration:** Use `woo_special_offer_render` in a template file to ensure the action hook renders without errors.
6. **Cross-Browser & Responsive Checks:** Review the offer widget on desktop, tablet, and mobile viewports to confirm responsive behavior.

## Contributing
Pull requests are welcome. Please ensure your updates include relevant documentation changes, adhere to WordPress coding standards, and remain compatible with supported PHP, WordPress, and WooCommerce versions. Run through the testing checklist above before submitting changes to keep the project stable and performant.

