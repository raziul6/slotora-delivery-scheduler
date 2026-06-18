<?php
/**
 * Plugin Name:       Slotora Delivery Scheduler for WooCommerce
 * Plugin URI:        https://yoursite.com/slotora
 * Description:       Let customers choose their delivery date and time slot at checkout. Supports blackout dates, slot limits, cutoff times, and delivery zone schedules. Built for WooCommerce stores that do local or scheduled delivery.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Raziul
 * Author URI:        https://byteflows.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       slotora-delivery-scheduler
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.5
 */

defined('ABSPATH') || exit;

// Plugin constants
define('SLOTORA_VERSION', '1.0.2');
define('SLOTORA_PLUGIN_FILE', __FILE__);
define('SLOTORA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SLOTORA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SLOTORA_PREFIX', 'slotora_');

/**
 * Declare WooCommerce HPOS and Cart/Checkout block compatibility.
 * Must run on 'before_woocommerce_init'.
 */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
});

/**
 * Check WooCommerce is active before doing anything.
 */
function slotora_check_woocommerce()
{
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'slotora_woocommerce_missing_notice');
		return false;
	}
	return true;
}

function slotora_woocommerce_missing_notice()
{
	echo '<div class="notice notice-error"><p>';
	echo esc_html__('Slotora Delivery Scheduler requires WooCommerce to be installed and active.', 'slotora-delivery-scheduler');
	echo '</p></div>';
}

/**
 * Main plugin loader — fires on plugins_loaded.
 * load_plugin_textdomain() removed: WordPress 4.6+ auto-loads translations
 * from the language directory when the plugin is hosted on WordPress.org.
 */
function slotora_init()
{
	if (!slotora_check_woocommerce()) {
		return;
	}

	// Core includes
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-helpers.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-settings.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-pro-stubs.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-slots.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-checkout.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-orders.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-emails.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-block-checkout.php';
	require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-block-integration.php';

	// Admin
	if (is_admin()) {
		require_once SLOTORA_PLUGIN_DIR . 'admin/class-slotora-admin.php';
		require_once SLOTORA_PLUGIN_DIR . 'admin/class-slotora-admin-orders.php';
	}

	// Boot singletons
	Slotora_Settings::get_instance();
	Slotora_Slots::get_instance();
	Slotora_Checkout::get_instance();
	Slotora_Orders::get_instance();
	Slotora_Emails::get_instance();
	Slotora_Block_Checkout::get_instance();

	if (is_admin()) {
		Slotora_Admin::get_instance();
		Slotora_Admin_Orders::get_instance();
	}
}
add_action('plugins_loaded', 'slotora_init');

/**
 * Activation hook — create DB table, set default options.
 */
function slotora_activate()
{
	global $wpdb;
	$table = $wpdb->prefix . 'slotora_slot_bookings';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table} (
		id           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		order_id     bigint(20) UNSIGNED NOT NULL DEFAULT 0,
		slot_date    date NOT NULL,
		slot_time    varchar(20) NOT NULL DEFAULT '',
		created_at   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY order_id (order_id),
		KEY slot_date (slot_date)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);

	$defaults = array(
		'slotora_enabled' => 'yes',
		'slotora_date_label' => __('Choose Delivery Date', 'slotora-delivery-scheduler'),
		'slotora_time_label' => __('Choose Time Slot', 'slotora-delivery-scheduler'),
		'slotora_min_days' => 1,
		'slotora_max_days' => 14,
		'slotora_cutoff_hour' => 14,
		'slotora_required' => 'yes',
		'slotora_blackout_dates' => array(),
		'slotora_working_days' => array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'),
		'slotora_time_slots' => array(
			array('label' => '9:00 AM – 12:00 PM', 'value' => '09:00-12:00', 'limit' => 0),
			array('label' => '12:00 PM – 3:00 PM', 'value' => '12:00-15:00', 'limit' => 0),
			array('label' => '3:00 PM – 6:00 PM', 'value' => '15:00-18:00', 'limit' => 0),
		),
		'slotora_show_in_email' => 'yes',
		'slotora_show_in_myaccount' => 'yes',
		'slotora_reminder_email' => 'no',
	);

	foreach ($defaults as $key => $value) {
		if (get_option($key) === false) {
			update_option($key, $value);
		}
	}

	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'slotora_activate');

/**
 * Deactivation hook.
 */
function slotora_deactivate()
{
	wp_clear_scheduled_hook('slotora_send_reminders');
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'slotora_deactivate');
