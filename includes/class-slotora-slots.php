<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Slots {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_slotora_get_slots',        array( $this, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_nopriv_slotora_get_slots', array( $this, 'ajax_get_slots' ) );
    }

    /**
     * AJAX: return available time slots for a given date.
     */
    public function ajax_get_slots() {
        check_ajax_referer( 'slotora_nonce', 'nonce' );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via Slotora_Helpers::sanitize_date(), which applies sanitize_text_field() and a strict format check.
        $date = isset( $_POST['date'] ) ? Slotora_Helpers::sanitize_date( wp_unslash( $_POST['date'] ) ) : false;

        if ( ! $date ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date.', 'slotora-delivery-scheduler' ) ) );
        }

        // Verify it's an available date
        $available = Slotora_Helpers::get_available_dates();
        if ( ! in_array( $date, $available, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Date not available.', 'slotora-delivery-scheduler' ) ) );
        }

        $slots = Slotora_Helpers::get_slots_for_date( $date );
        wp_send_json_success( array( 'slots' => $slots ) );
    }
}
