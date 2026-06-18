<?php
/**
 * Slotora Block Integration
 *
 * Implements WooCommerce's IntegrationInterface so our CSS
 * loads correctly inside the Block checkout React context.
 *
 * The IntegrationRegistry guarantees our styles are available
 * when the React checkout block renders, which is different
 * from the normal wp_enqueue_scripts flow.
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Slotora_Block_Integration implements IntegrationInterface {

    /**
     * Unique name for this integration.
     *
     * @return string
     */
    public function get_name() {
        return 'slotora-delivery-scheduler';
    }

    /**
     * Initialization — register scripts and styles here.
     */
    public function initialize() {
        wp_register_style(
            'slotora-checkout',
            SLOTORA_PLUGIN_URL . 'public/css/slotora-checkout.css',
            array(),
            SLOTORA_VERSION
        );

        // Register a minimal frontend script (non-React — the additional
        // checkout fields API renders the inputs natively, we just style them
        // and add our calendar picker via the block_checkout_additional JS).
        if ( file_exists( SLOTORA_PLUGIN_DIR . 'public/js/dsd-block-checkout.js' ) ) {
            wp_register_script(
                'slotora-block-checkout',
                SLOTORA_PLUGIN_URL . 'public/js/dsd-block-checkout.js',
                array( 'wc-blocks-checkout', 'wp-element', 'wp-html-entities' ),
                SLOTORA_VERSION,
                true
            );
        }
    }

    /**
     * Scripts to enqueue on the frontend (checkout page).
     *
     * @return array
     */
    public function get_script_handles() {
        return file_exists( SLOTORA_PLUGIN_DIR . 'public/js/dsd-block-checkout.js' )
            ? array( 'slotora-block-checkout' )
            : array();
    }

    /**
     * Scripts to enqueue in the block editor.
     *
     * @return array
     */
    public function get_editor_script_handles() {
        return array();
    }

    /**
     * Data made available to JS on the client side via wc.wcSettings.getSetting().
     *
     * @return array
     */
    public function get_script_data() {
        return array(
            'availableDates' => Slotora_Helpers::get_available_dates(),
            'hasTimeSlots'   => count( Slotora_Settings::get_time_slots() ) > 0,
            'required'       => Slotora_Settings::is_required(),
            'i18n'           => array(
                'dateLabel'   => get_option( 'slotora_date_label', __( 'Delivery Date', 'slotora-delivery-scheduler' ) ),
                'selectDate'  => __( 'Please select a delivery date.', 'slotora-delivery-scheduler' ),
                'selectSlot'  => __( 'Please select a time slot.', 'slotora-delivery-scheduler' ),
                'noDates'     => __( 'No delivery dates are currently available.', 'slotora-delivery-scheduler' ),
            ),
        );
    }
}
