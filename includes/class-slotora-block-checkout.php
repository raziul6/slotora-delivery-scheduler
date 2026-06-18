<?php
/**
 * Slotora Block Checkout Integration
 *
 * WooCommerce has TWO checkout types:
 *
 * 1. Classic checkout — uses [woocommerce_checkout] shortcode / PHP hooks.
 *    Our woocommerce_after_order_notes hook works perfectly here.
 *
 * 2. Block checkout (Gutenberg) — a React app. PHP hooks like
 *    woocommerce_after_order_notes are IGNORED. We need a completely
 *    different approach using:
 *    - woocommerce_register_additional_checkout_field() to add our fields
 *    - woocommerce_set_additional_field_value to save values to order meta
 *    - StoreAPI checkout processing hooks to validate
 *
 * This class handles Block Checkout. The existing Slotora_Checkout class
 * continues to handle Classic Checkout. Both run in parallel — WooCommerce
 * only renders one checkout type at a time so there's no duplication.
 */

defined( 'ABSPATH' ) || exit;

class Slotora_Block_Checkout {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! Slotora_Settings::is_enabled() ) return;

        // Register fields after WC fully loads
        add_action( 'woocommerce_init', array( $this, 'register_fields' ) );

        // Enqueue our assets for the Block checkout context
        add_action( 'woocommerce_blocks_checkout_block_registration', array( $this, 'register_block_integration' ) );

        // Save delivery date + slot to order when placed via Block checkout
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_to_order_block' ) );

        // Validate via StoreAPI
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'validate_block' ), 10, 2 );
    }

    /**
     * Register the delivery date and time slot as Additional Checkout Fields.
     * These appear in the "Additional Information" section of the Block checkout.
     * Available since WooCommerce 8.9 (stable).
     */
    public function register_fields() {
        if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
            return; // WC < 8.9 — block checkout fields API unavailable
        }

        $date_label = get_option( 'slotora_date_label', __( 'Delivery Date', 'slotora-delivery-scheduler' ) );
        $time_label = get_option( 'slotora_time_label', __( 'Delivery Time Slot', 'slotora-delivery-scheduler' ) );
        $required   = Slotora_Settings::is_required();
        $slots      = Slotora_Settings::get_time_slots();

        // --- Delivery Date field ---
        woocommerce_register_additional_checkout_field(
            array(
                'id'       => 'slotora-delivery-scheduler/delivery-date',
                'label'    => $date_label,
                'location' => 'order',  // "Additional order information" section
                'required' => $required,
                'type'     => 'text',
                'attributes' => array(
                    'autocomplete' => 'off',
                    'data-dsd'     => 'date',
                    'placeholder'  => 'YYYY-MM-DD',
                ),
                'sanitize_callback' => function( $value ) {
                    return Slotora_Helpers::sanitize_date( $value ) ?: '';
                },
                'validate_callback' => array( $this, 'validate_date_field' ),
            )
        );

        // --- Time Slot field (only if slots are configured) ---
        if ( ! empty( $slots ) ) {
            $options = array();
            foreach ( $slots as $slot ) {
                $options[] = array(
                    'value' => $slot['value'],
                    'label' => $slot['label'],
                );
            }

            woocommerce_register_additional_checkout_field(
                array(
                    'id'       => 'slotora-delivery-scheduler/delivery-slot',
                    'label'    => $time_label,
                    'location' => 'order',
                    'required' => $required,
                    'type'     => 'select',
                    'options'  => $options,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array( $this, 'validate_slot_field' ),
                )
            );
        }
    }

    /**
     * Validate delivery date field.
     *
     * The Block checkout additional-fields API calls validate callbacks as
     * ( $value, $field ) and expects a WP_Error returned on failure (or null
     * when valid) — NOT the classic ( $errors, $value ) signature.
     *
     * @param  mixed $value The submitted field value.
     * @param  array $field The field definition.
     * @return WP_Error|void
     */
    public function validate_date_field( $value, $field ) {
        if ( empty( $value ) ) {
            return; // required check is handled by WC itself
        }

        $date = Slotora_Helpers::sanitize_date( $value );
        if ( ! $date ) {
            return new WP_Error( 'slotora_invalid_date', __( 'Please enter a valid delivery date (YYYY-MM-DD).', 'slotora-delivery-scheduler' ) );
        }

        $available = Slotora_Helpers::get_available_dates();
        if ( ! in_array( $date, $available, true ) ) {
            return new WP_Error( 'slotora_unavailable_date', __( 'The selected delivery date is not available. Please choose another date.', 'slotora-delivery-scheduler' ) );
        }
    }

    /**
     * Validate time slot field.
     *
     * Capacity depends on the chosen date, which is not available here, so the
     * final capacity check happens in save_to_order_block(). This callback just
     * satisfies the Block checkout ( $value, $field ) signature.
     *
     * @param  mixed $value The submitted field value.
     * @param  array $field The field definition.
     * @return WP_Error|void
     */
    public function validate_slot_field( $value, $field ) {
        // Capacity is validated in save_to_order_block().
    }

    /**
     * Validate + save when Block checkout order is created.
     *
     * @param WC_Order $order
     */
    public function save_to_order_block( $order ) {
        $date = $order->get_meta( 'slotora-delivery-scheduler/delivery-date' );
        $slot = $order->get_meta( 'slotora-delivery-scheduler/delivery-slot' );

        // Normalize: WC stores additional fields under the registered ID
        if ( empty( $date ) ) {
            // Try the StoreAPI field meta key format
            $date = $order->get_meta( '_wc_additional_field_slotora-delivery-scheduler/delivery-date' );
            $slot = $order->get_meta( '_wc_additional_field_slotora-delivery-scheduler/delivery-slot' );
        }

        $date = Slotora_Helpers::sanitize_date( $date );
        $slot = sanitize_text_field( $slot );

        if ( $date ) {
            // Check slot capacity one final time
            if ( $slot ) {
                $slots_for_date = Slotora_Helpers::get_slots_for_date( $date );
                foreach ( $slots_for_date as $s ) {
                    if ( $s['value'] === $slot && $s['full'] ) {
                        // Slot filled between select and submit — block order
                        throw new \Exception(
                            esc_html__( 'Sorry, the selected delivery time slot is now fully booked. Please go back and choose another slot.', 'slotora-delivery-scheduler' )
                        );
                    }
                }
            }

            // Save to order meta using our own keys (consistent with Classic checkout)
            $order->update_meta_data( '_slotora_delivery_date', $date );

            if ( $slot ) {
                $order->update_meta_data( '_slotora_delivery_slot', $slot );
                // Find and save the human-readable label
                $configured = Slotora_Settings::get_time_slots();
                foreach ( $configured as $s ) {
                    if ( $s['value'] === $slot ) {
                        $order->update_meta_data( '_slotora_delivery_slot_label', $s['label'] );
                        break;
                    }
                }
            }

            $order->save();

            // Record in booking table (slot capacity tracking)
            Slotora_Helpers::save_booking( $order->get_id(), $date, $slot );
            do_action( 'slotora_after_booking_saved', $order->get_id(), $date, $slot );
        }
    }

    /**
     * Extra validation during StoreAPI order update (called before order is placed).
     *
     * @param WC_Order         $order
     * @param WP_REST_Request  $request
     */
    public function validate_block( $order, $request ) {
        if ( ! Slotora_Settings::is_required() ) return;

        $additional = $request->get_param( 'additional_fields' ) ?? array();
        $date_key   = 'slotora-delivery-scheduler/delivery-date';
        $slot_key   = 'slotora-delivery-scheduler/delivery-slot';
        $date       = isset( $additional[ $date_key ] ) ? Slotora_Helpers::sanitize_date( $additional[ $date_key ] ) : '';
        $slots      = Slotora_Settings::get_time_slots();

        if ( empty( $date ) ) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                'slotora_missing_date',
                esc_html__( 'Please select a delivery date before placing your order.', 'slotora-delivery-scheduler' ),
                400
            );
        }

        if ( ! empty( $slots ) ) {
            $slot = isset( $additional[ $slot_key ] ) ? sanitize_text_field( $additional[ $slot_key ] ) : '';
            if ( empty( $slot ) ) {
                throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                    'slotora_missing_slot',
                    esc_html__( 'Please select a delivery time slot before placing your order.', 'slotora-delivery-scheduler' ),
                    400
                );
            }
        }
    }

    /**
     * Register Block integration for enqueuing assets in the Block checkout context.
     *
     * @param \Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry $registry
     */
    public function register_block_integration( $registry ) {
        require_once SLOTORA_PLUGIN_DIR . 'includes/class-slotora-block-integration.php';
        $registry->register( new Slotora_Block_Integration() );
    }
}
