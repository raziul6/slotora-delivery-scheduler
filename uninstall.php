<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$slotora_options = array(
	'slotora_enabled', 'slotora_date_label', 'slotora_time_label',
	'slotora_min_days', 'slotora_max_days', 'slotora_cutoff_hour',
	'slotora_required', 'slotora_blackout_dates', 'slotora_working_days',
	'slotora_time_slots', 'slotora_show_in_email', 'slotora_show_in_myaccount',
	'slotora_reminder_email',
);
foreach ( $slotora_options as $slotora_opt ) {
	delete_option( $slotora_opt );
}

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Dropping the plugin's own custom table on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}slotora_slot_bookings" );
wp_clear_scheduled_hook( 'slotora_send_reminders' );
