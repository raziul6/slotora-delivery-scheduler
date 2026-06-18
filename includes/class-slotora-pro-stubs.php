<?php
/**
 * Slotora Delivery Scheduler PRO — Pro Feature Stubs & Upgrade Hooks
 *
 * This file is bundled with the free version to:
 * 1. Define upgrade filters for easy Pro extension
 * 2. Show "Pro" locked UI hints without loading Pro code
 *
 * The actual Pro plugin extends these hooks when activated alongside the free version.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ============================================================
 * PRO FEATURE REGISTRY
 * Each feature has a filter that Pro overrides.
 * ============================================================
 */

/**
 * Filter: slotora_pro_is_active
 * Pro plugin returns true. Free always returns false.
 */
add_filter( 'slotora_pro_is_active', '__return_false' );

/**
 * Filter: slotora_pro_sms_enabled
 * Pro enables SMS reminders via Twilio/local gateway.
 */
add_filter( 'slotora_pro_sms_enabled', '__return_false' );

/**
 * Filter: slotora_pro_whatsapp_enabled
 * Pro enables WhatsApp notifications.
 */
add_filter( 'slotora_pro_whatsapp_enabled', '__return_false' );

/**
 * Filter: slotora_pro_delivery_zones_enabled
 * Pro enables per-zone delivery schedules.
 */
add_filter( 'slotora_pro_delivery_zones_enabled', '__return_false' );

/**
 * Filter: slotora_pro_multistore_enabled
 * Pro enables multi-location scheduling.
 */
add_filter( 'slotora_pro_multistore_enabled', '__return_false' );

/**
 * Filter: slotora_pro_export_enabled
 * Pro enables CSV export of delivery schedule.
 */
add_filter( 'slotora_pro_export_enabled', '__return_false' );

/**
 * Filter: slotora_available_dates
 * Pro can inject zone-specific available dates.
 *
 * @param array $dates Array of Y-m-d strings.
 * @return array
 */
add_filter( 'slotora_available_dates', function( $dates ) {
    return $dates;
} );

/**
 * Filter: slotora_time_slots_for_date
 * Pro can return zone-specific slots for a date.
 *
 * @param array  $slots Available slots array.
 * @param string $date  Y-m-d
 * @return array
 */
add_filter( 'slotora_time_slots_for_date', function( $slots, $date ) {
    return $slots;
}, 10, 2 );

/**
 * Action: slotora_after_booking_saved
 * Fired by the free plugin after a delivery booking is saved.
 * Pro hooks here to trigger SMS/WhatsApp confirmations.
 *
 * @param int    $order_id
 * @param string $date
 * @param string $slot
 */

/**
 * ============================================================
 * PRO FEATURE DESCRIPTIONS (used in admin upgrade notices)
 * ============================================================
 */
function slotora_get_pro_features() {
    return array(
        'sms_reminders' => array(
            'title' => __( 'SMS Delivery Reminders', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Send automated SMS reminders to customers before their delivery via Twilio or local SMS gateways.', 'slotora-delivery-scheduler' ),
        ),
        'whatsapp' => array(
            'title' => __( 'WhatsApp Notifications', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Send delivery confirmations and reminders via WhatsApp Business API.', 'slotora-delivery-scheduler' ),
        ),
        'delivery_zones' => array(
            'title' => __( 'Delivery Zone Schedules', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Configure different available days and time slots for different delivery zones or postal codes.', 'slotora-delivery-scheduler' ),
        ),
        'multi_location' => array(
            'title' => __( 'Multi-Location Support', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Manage delivery slots for multiple store locations independently.', 'slotora-delivery-scheduler' ),
        ),
        'export' => array(
            'title' => __( 'Delivery Schedule Export', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Export upcoming deliveries by date as CSV for drivers and fulfilment teams.', 'slotora-delivery-scheduler' ),
        ),
        'slot_products' => array(
            'title' => __( 'Per-Product Slot Rules', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Set different slot availability or lead times for specific products (e.g. fresh vs. frozen).', 'slotora-delivery-scheduler' ),
        ),
        'custom_reminder' => array(
            'title' => __( 'Custom Reminder Timing', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Send reminders 1 day, 2 days, or a custom number of hours before delivery.', 'slotora-delivery-scheduler' ),
        ),
        'deposits' => array(
            'title' => __( 'Slot Deposit on Booking', 'slotora-delivery-scheduler' ),
            'desc'  => __( 'Charge a small deposit when a time slot is booked to reduce no-shows.', 'slotora-delivery-scheduler' ),
        ),
    );
}
