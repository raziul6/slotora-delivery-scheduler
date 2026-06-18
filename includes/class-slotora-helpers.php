<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Helpers {

    /**
     * Get all booked slot counts for a given date.
     *
     * @param string $date  Y-m-d
     * @param string $slot  slot value e.g. 09:00-12:00
     * @return int
     */
    public static function get_slot_booking_count( $date, $slot ) {
        global $wpdb;
        $table = $wpdb->prefix . 'slotora_slot_bookings';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom bookings table built from $wpdb->prefix; live booking counts must not be cached.
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE slot_date = %s AND slot_time = %s",
                $date,
                $slot
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $count;
    }

    /**
     * Get all booked slots for a date, keyed by slot value => count.
     *
     * @param string $date Y-m-d
     * @return array
     */
    public static function get_date_bookings( $date ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'slotora_slot_bookings';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom bookings table built from $wpdb->prefix; live booking counts must not be cached.
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT slot_time, COUNT(*) as cnt FROM {$table} WHERE slot_date = %s GROUP BY slot_time",
                $date
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $map = array();
        foreach ( $results as $row ) {
            $map[ $row['slot_time'] ] = (int) $row['cnt'];
        }
        return $map;
    }

    /**
     * Save a booking record.
     *
     * @param int    $order_id
     * @param string $date
     * @param string $slot
     * @return bool
     */
    public static function save_booking( $order_id, $date, $slot ) {
        global $wpdb;
        $table = $wpdb->prefix . 'slotora_slot_bookings';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom bookings table; insert into a non-core table, nothing to cache.
        $inserted = $wpdb->insert(
            $table,
            array(
                'order_id'  => absint( $order_id ),
                'slot_date' => sanitize_text_field( $date ),
                'slot_time' => sanitize_text_field( $slot ),
            ),
            array( '%d', '%s', '%s' )
        );
        return $inserted !== false;
    }

    /**
     * Remove a booking by order ID (used when order is cancelled/deleted).
     *
     * @param int $order_id
     */
    public static function remove_booking( $order_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'slotora_slot_bookings';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom bookings table; delete from a non-core table, nothing to cache.
        $wpdb->delete( $table, array( 'order_id' => absint( $order_id ) ), array( '%d' ) );
    }

    /**
     * Get available dates as array for the calendar JS.
     * Returns JSON-safe array of date strings (Y-m-d) that are available.
     *
     * @return array
     */
    public static function get_available_dates() {
        $min_days    = (int) get_option( 'slotora_min_days', 1 );
        $max_days    = (int) get_option( 'slotora_max_days', 14 );
        $cutoff_hour = (int) get_option( 'slotora_cutoff_hour', 14 );
        $working_days = (array) get_option( 'slotora_working_days', array( 'mon','tue','wed','thu','fri','sat' ) );
        $blackout    = (array) get_option( 'slotora_blackout_dates', array() );

        $day_map = array(
            'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3,
            'thu' => 4, 'fri' => 5, 'sat' => 6,
        );
        $working_nums = array();
        foreach ( $working_days as $d ) {
            if ( isset( $day_map[ $d ] ) ) {
                $working_nums[] = $day_map[ $d ];
            }
        }

        $now_dt       = current_datetime();
        $hour_now     = (int) $now_dt->format( 'G' );
        $start_offset = $min_days;

        // If past cutoff, push start by 1
        if ( $hour_now >= $cutoff_hour ) {
            $start_offset++;
        }

        // Build the candidate range from "today" in the site timezone so the
        // results are not affected by runtime timezone changes.
        $today     = new DateTimeImmutable( $now_dt->format( 'Y-m-d' ), wp_timezone() );
        $available = array();

        for ( $i = $start_offset; $i <= $max_days; $i++ ) {
            $day = $today->modify( "+{$i} days" );
            $dow = (int) $day->format( 'w' );
            $ymd = $day->format( 'Y-m-d' );
            if ( in_array( $dow, $working_nums, true ) && ! in_array( $ymd, $blackout, true ) ) {
                $available[] = $ymd;
            }
        }

        return apply_filters( 'slotora_available_dates', $available );
    }

    /**
     * Get available time slots for a given date, with availability info.
     *
     * @param  string $date Y-m-d
     * @return array
     */
    public static function get_slots_for_date( $date ) {
        $slots    = (array) get_option( 'slotora_time_slots', array() );
        $bookings = self::get_date_bookings( $date );
        $result   = array();

        foreach ( $slots as $slot ) {
            $value   = isset( $slot['value'] ) ? $slot['value'] : '';
            $label   = isset( $slot['label'] ) ? $slot['label'] : '';
            $limit   = isset( $slot['limit'] ) ? (int) $slot['limit'] : 0;
            $booked  = isset( $bookings[ $value ] ) ? $bookings[ $value ] : 0;
            $full    = ( $limit > 0 && $booked >= $limit );

            $result[] = array(
                'value'   => $value,
                'label'   => $label,
                'limit'   => $limit,
                'booked'  => $booked,
                'full'    => $full,
            );
        }

        return apply_filters( 'slotora_time_slots_for_date', $result, $date );
    }

    /**
     * Format a date nicely.
     *
     * @param  string $ymd
     * @return string
     */
    public static function format_date( $ymd ) {
        if ( empty( $ymd ) ) return '';
        $ts = strtotime( $ymd );
        return date_i18n( get_option( 'date_format' ), $ts );
    }

    /**
     * Sanitize a Y-m-d date string.
     *
     * @param  string $date
     * @return string|false
     */
    public static function sanitize_date( $date ) {
        $date = sanitize_text_field( $date );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return $date;
        }
        return false;
    }
}
