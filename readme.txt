=== Slotora Delivery Scheduler for WooCommerce ===
Contributors: raziul
Tags: woocommerce, delivery date, time slots, scheduled delivery, booking
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let WooCommerce customers pick a delivery date and time slot at checkout, with blackout dates, per-slot limits, working days, and cutoff times.

== Description ==

**Slotora Delivery Scheduler** adds a beautiful, interactive delivery date and time slot picker to your WooCommerce checkout. Perfect for bakeries, flower shops, grocery stores, food delivery services, and any business that does local or scheduled delivery.

Customers see a clean inline calendar, pick an available date, then choose from your configured time slots — all without leaving the checkout page.

**Free Features:**

* 📅 Interactive inline calendar date picker at checkout
* ⏰ Configurable time slots (e.g. Morning, Afternoon, Evening)
* 🔒 Per-slot booking limits — prevent overbooking
* 🚫 Blackout dates — block holidays and closures
* 📆 Working days configuration
* ⏱ Minimum lead time and maximum booking window
* ⛔ Same-day order cutoff time
* ✅ Required field validation
* 📧 Delivery info in order confirmation emails
* 📋 Delivery date in My Account order view
* 📊 Delivery date column in WooCommerce orders list
* 🔔 Delivery reminder email (day before)
* 🗑 Automatic slot release when orders are cancelled

**Compatible with:**

* WooCommerce 7.0+
* WordPress block themes and classic themes
* WooCommerce HPOS (High-Performance Order Storage)
* WooCommerce Block Checkout (Gutenberg)

== Installation ==

1. Upload the `slotora-delivery-scheduler` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **WooCommerce → Delivery Slots** to configure settings
4. Test by visiting your checkout page

== Frequently Asked Questions ==

= Does this work with block themes and the Gutenberg checkout block? =
Yes. Slotora supports both the classic WooCommerce checkout shortcode and the WooCommerce Blocks checkout.

= Can I make the delivery date optional? =
Yes — go to **WooCommerce → Delivery Slots → General** and disable "Required Field".

= How do I block a public holiday? =
Go to **WooCommerce → Delivery Slots → Blackout Dates** and add the date.

= What happens to a slot booking when an order is cancelled? =
The booking is automatically released, freeing the slot for other customers.

= Does this work with WooCommerce HPOS? =
Yes, fully compatible with High-Performance Order Storage.

== Screenshots ==

1. Checkout calendar and time slot picker
2. General settings
3. Time slot configuration
4. Blackout dates management
5. Delivery info on order confirmation page
6. Delivery date column in WooCommerce orders list

== Changelog ==

= 1.0.2 =
* Fix: Declared WooCommerce HPOS and Cart/Checkout Block compatibility.
* Fix: Full support for WooCommerce Block Checkout.
* Fix: Classic checkout picker skips rendering when Block checkout is active.
* Improvement: Added Requires Plugins header for WooCommerce dependency.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Fixes WooCommerce incompatibility warning. Adds Block Checkout support.
