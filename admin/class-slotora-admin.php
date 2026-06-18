<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',            array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_slotora_save_settings',  array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_slotora_add_blackout',   array( $this, 'ajax_add_blackout' ) );
        add_action( 'wp_ajax_slotora_remove_blackout',array( $this, 'ajax_remove_blackout' ) );
    }

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Delivery Slots', 'slotora-delivery-scheduler' ),
            __( 'Delivery Slots', 'slotora-delivery-scheduler' ),
            'manage_woocommerce',
            'slotora-delivery-scheduler',
            array( $this, 'render_page' )
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'slotora-delivery-scheduler' ) === false ) return;

        wp_enqueue_style(
            'slotora-admin',
            SLOTORA_PLUGIN_URL . 'admin/css/slotora-admin.css',
            array(),
            SLOTORA_VERSION
        );

        wp_enqueue_script(
            'slotora-admin',
            SLOTORA_PLUGIN_URL . 'admin/js/slotora-admin.js',
            array( 'jquery' ),
            SLOTORA_VERSION,
            true
        );

        wp_localize_script( 'slotora-admin', 'slotoraAdmin', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'slotora_admin_nonce' ),
            'settings' => $this->get_current_settings(),
            'i18n'     => array(
                'saved'   => __( 'Settings saved!', 'slotora-delivery-scheduler' ),
                'error'   => __( 'Something went wrong. Please try again.', 'slotora-delivery-scheduler' ),
                'confirm' => __( 'Remove this blackout date?', 'slotora-delivery-scheduler' ),
            ),
        ) );
    }

    private function get_current_settings() {
        return array(
            'enabled'       => get_option( 'slotora_enabled', 'yes' ),
            'date_label'    => get_option( 'slotora_date_label', __( 'Choose Delivery Date', 'slotora-delivery-scheduler' ) ),
            'time_label'    => get_option( 'slotora_time_label', __( 'Choose Time Slot', 'slotora-delivery-scheduler' ) ),
            'min_days'      => (int) get_option( 'slotora_min_days', 1 ),
            'max_days'      => (int) get_option( 'slotora_max_days', 14 ),
            'cutoff_hour'   => (int) get_option( 'slotora_cutoff_hour', 14 ),
            'required'      => get_option( 'slotora_required', 'yes' ),
            'working_days'  => (array) get_option( 'slotora_working_days', array( 'mon','tue','wed','thu','fri','sat' ) ),
            'time_slots'    => Slotora_Settings::get_time_slots(),
            'blackout_dates'=> Slotora_Settings::get_blackout_dates(),
            'show_in_email' => get_option( 'slotora_show_in_email', 'yes' ),
            'reminder_email'=> get_option( 'slotora_reminder_email', 'no' ),
        );
    }

    public function ajax_save_settings() {
        check_ajax_referer( 'slotora_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw serialized form string; each parsed field is sanitized individually below.
        $data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';
        parse_str( $data, $fields );

        update_option( 'slotora_enabled',       isset( $fields['slotora_enabled'] ) ? 'yes' : 'no' );
        update_option( 'slotora_required',      isset( $fields['slotora_required'] ) ? 'yes' : 'no' );
        update_option( 'slotora_show_in_email', isset( $fields['slotora_show_in_email'] ) ? 'yes' : 'no' );
        update_option( 'slotora_reminder_email',isset( $fields['slotora_reminder_email'] ) ? 'yes' : 'no' );

        update_option( 'slotora_date_label',  sanitize_text_field( $fields['slotora_date_label'] ?? '' ) );
        update_option( 'slotora_time_label',  sanitize_text_field( $fields['slotora_time_label'] ?? '' ) );
        update_option( 'slotora_min_days',    absint( $fields['slotora_min_days'] ?? 1 ) );
        update_option( 'slotora_max_days',    absint( $fields['slotora_max_days'] ?? 14 ) );
        update_option( 'slotora_cutoff_hour', absint( $fields['slotora_cutoff_hour'] ?? 14 ) );

        $working = isset( $fields['slotora_working_days'] ) && is_array( $fields['slotora_working_days'] )
            ? array_map( 'sanitize_text_field', $fields['slotora_working_days'] )
            : array();
        update_option( 'slotora_working_days', $working );

        // Save time slots
        $slots = array();
        if ( ! empty( $fields['slotora_slot_label'] ) && is_array( $fields['slotora_slot_label'] ) ) {
            foreach ( $fields['slotora_slot_label'] as $i => $lbl ) {
                $val   = isset( $fields['slotora_slot_value'][ $i ] ) ? sanitize_text_field( $fields['slotora_slot_value'][ $i ] ) : '';
                $limit = isset( $fields['slotora_slot_limit'][ $i ] ) ? absint( $fields['slotora_slot_limit'][ $i ] ) : 0;
                if ( $val ) {
                    $slots[] = array(
                        'label' => sanitize_text_field( $lbl ),
                        'value' => $val,
                        'limit' => $limit,
                    );
                }
            }
        }
        Slotora_Settings::save_time_slots( $slots );

        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'slotora-delivery-scheduler' ) ) );
    }

    public function ajax_add_blackout() {
        check_ajax_referer( 'slotora_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via Slotora_Helpers::sanitize_date(), which applies sanitize_text_field() and a strict format check.
        $date = isset( $_POST['date'] ) ? Slotora_Helpers::sanitize_date( wp_unslash( $_POST['date'] ) ) : false;
        if ( ! $date ) wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'slotora-delivery-scheduler' ) ) );

        $dates = Slotora_Settings::get_blackout_dates();
        if ( ! in_array( $date, $dates, true ) ) {
            $dates[] = $date;
            Slotora_Settings::save_blackout_dates( $dates );
        }
        wp_send_json_success( array( 'dates' => Slotora_Settings::get_blackout_dates() ) );
    }

    public function ajax_remove_blackout() {
        check_ajax_referer( 'slotora_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error();

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via Slotora_Helpers::sanitize_date(), which applies sanitize_text_field() and a strict format check.
        $date  = isset( $_POST['date'] ) ? Slotora_Helpers::sanitize_date( wp_unslash( $_POST['date'] ) ) : false;
        if ( ! $date ) wp_send_json_error();

        $dates = Slotora_Settings::get_blackout_dates();
        $dates = array_values( array_filter( $dates, fn( $d ) => $d !== $date ) );
        Slotora_Settings::save_blackout_dates( $dates );

        wp_send_json_success( array( 'dates' => Slotora_Settings::get_blackout_dates() ) );
    }

    public function render_page() {
        include SLOTORA_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
}
