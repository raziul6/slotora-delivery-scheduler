<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Settings {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Get a setting value with a fallback default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get( $key, $default = '' ) {
        $key = str_starts_with( $key, 'slotora_' ) ? $key : 'slotora_' . $key;
        return get_option( $key, $default );
    }

    /**
     * Is the plugin enabled?
     */
    public static function is_enabled() {
        return get_option( 'slotora_enabled', 'yes' ) === 'yes';
    }

    /**
     * Is date/time selection required at checkout?
     */
    public static function is_required() {
        return get_option( 'slotora_required', 'yes' ) === 'yes';
    }

    /**
     * Get configured time slots.
     *
     * @return array
     */
    public static function get_time_slots() {
        $slots = get_option( 'slotora_time_slots', array() );
        if ( ! is_array( $slots ) ) return array();
        return $slots;
    }

    /**
     * Save time slots.
     *
     * @param array $slots
     */
    public static function save_time_slots( $slots ) {
        $clean = array();
        foreach ( $slots as $slot ) {
            $clean[] = array(
                'label' => sanitize_text_field( $slot['label'] ),
                'value' => sanitize_text_field( $slot['value'] ),
                'limit' => absint( $slot['limit'] ),
            );
        }
        update_option( 'slotora_time_slots', $clean );
    }

    /**
     * Get blackout dates.
     *
     * @return array
     */
    public static function get_blackout_dates() {
        $dates = get_option( 'slotora_blackout_dates', array() );
        return is_array( $dates ) ? $dates : array();
    }

    /**
     * Save blackout dates.
     *
     * @param array $dates
     */
    public static function save_blackout_dates( $dates ) {
        $clean = array();
        foreach ( $dates as $d ) {
            $sanitized = Slotora_Helpers::sanitize_date( $d );
            if ( $sanitized ) {
                $clean[] = $sanitized;
            }
        }
        $clean = array_unique( $clean );
        update_option( 'slotora_blackout_dates', array_values( $clean ) );
    }
}
