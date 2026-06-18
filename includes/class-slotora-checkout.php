<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Checkout {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! Slotora_Settings::is_enabled() ) return;

        // Classic checkout hooks. The Block checkout ignores these hooks entirely,
        // so there is no duplication — but we guard render_picker() too.
        add_action( 'wp_enqueue_scripts',                  array( $this, 'enqueue_assets' ) );
        add_action( 'woocommerce_after_order_notes',       array( $this, 'render_picker' ) );
        add_action( 'woocommerce_checkout_process',        array( $this, 'validate_selection' ) );
        add_action( 'woocommerce_checkout_order_created',  array( $this, 'save_to_order' ) );
    }

    /**
     * Check if the checkout page uses the Gutenberg Block checkout.
     * If so, our Classic PHP picker should not render — the Block checkout
     * uses Slotora_Block_Checkout instead (registered fields + StoreAPI).
     *
     * @return bool
     */
    private function is_block_checkout() {
        $checkout_page_id = wc_get_page_id( "checkout" );
        if ( $checkout_page_id > 0 ) {
            $post = get_post( $checkout_page_id );
            if ( $post && has_block( "woocommerce/checkout", $post ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enqueue frontend CSS + JS only on checkout.
     */
    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        if ( $this->is_block_checkout() ) return; // Block checkout handles its own assets

        wp_enqueue_style(
            'slotora-checkout',
            SLOTORA_PLUGIN_URL . 'public/css/slotora-checkout.css',
            array(),
            SLOTORA_VERSION
        );

        wp_enqueue_script(
            'slotora-checkout',
            SLOTORA_PLUGIN_URL . 'public/js/slotora-checkout.js',
            array( 'jquery' ),
            SLOTORA_VERSION,
            true
        );

        $available_dates = Slotora_Helpers::get_available_dates();
        $has_time_slots  = count( Slotora_Settings::get_time_slots() ) > 0;

        wp_localize_script( 'slotora-checkout', 'slotoraData', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'slotora_nonce' ),
            'availableDates' => $available_dates,
            'hasTimeSlots'   => $has_time_slots,
            'required'       => Slotora_Settings::is_required(),
            'i18n'           => array(
                'selectDate'    => __( 'Please select a delivery date.', 'slotora-delivery-scheduler' ),
                'selectSlot'    => __( 'Please select a delivery time slot.', 'slotora-delivery-scheduler' ),
                'slotFull'      => __( 'This slot is fully booked. Please choose another.', 'slotora-delivery-scheduler' ),
                'loadingSlots'  => __( 'Loading available slots…', 'slotora-delivery-scheduler' ),
                'noSlots'       => __( 'No time slots available for this date.', 'slotora-delivery-scheduler' ),
            ),
        ) );
    }

    /**
     * Render the delivery date + slot picker in checkout.
     */
    public function render_picker() {
        if ( ! Slotora_Settings::is_enabled() ) return;
        // Block checkout: our field is registered via woocommerce_register_additional_checkout_field().
        // The classic HTML picker should NOT render inside the React block.
        if ( $this->is_block_checkout() ) return;

        $date_label = get_option( 'slotora_date_label', __( 'Choose Delivery Date', 'slotora-delivery-scheduler' ) );
        $time_label = get_option( 'slotora_time_label', __( 'Choose Time Slot', 'slotora-delivery-scheduler' ) );
        $required   = Slotora_Settings::is_required();
        $has_slots  = count( Slotora_Settings::get_time_slots() ) > 0;

        ?>
        <div class="dsd-delivery-picker" id="slotora-delivery-picker">

            <div class="dsd-picker-header">
                <span class="dsd-picker-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                <div>
                    <h3 class="dsd-picker-title"><?php echo esc_html__( 'Schedule Your Delivery', 'slotora-delivery-scheduler' ); ?></h3>
                    <p class="dsd-picker-subtitle"><?php echo esc_html__( 'Pick a date and time that works for you', 'slotora-delivery-scheduler' ); ?></p>
                </div>
            </div>

            <div class="dsd-fields-wrap">

                <!-- Date field -->
                <div class="dsd-field-group" id="dsd-date-group">
                    <label class="dsd-label" for="dsd_delivery_date">
                        <?php echo esc_html( $date_label ); ?>
                        <?php if ( $required ) : ?><span class="dsd-required" aria-label="required">*</span><?php endif; ?>
                    </label>
                    <div class="dsd-calendar-wrap">
                        <div class="dsd-cal-nav">
                            <button type="button" class="dsd-cal-btn dsd-cal-prev" aria-label="<?php esc_attr_e( 'Previous month', 'slotora-delivery-scheduler' ); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <span class="dsd-cal-month-label"></span>
                            <button type="button" class="dsd-cal-btn dsd-cal-next" aria-label="<?php esc_attr_e( 'Next month', 'slotora-delivery-scheduler' ); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                        <div class="dsd-cal-weekdays">
                            <span><?php echo esc_html_x( 'Su', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'Mo', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'Tu', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'We', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'Th', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'Fr', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                            <span><?php echo esc_html_x( 'Sa', 'weekday short', 'slotora-delivery-scheduler' ); ?></span>
                        </div>
                        <div class="dsd-cal-grid" id="slotora-cal-grid"></div>
                    </div>
                    <input type="hidden" name="slotora_delivery_date" id="slotora_delivery_date" value="" />
                    <div class="dsd-selected-date-display" id="slotora-selected-date-display" style="display:none;"></div>
                </div>

                <!-- Time slot field -->
                <?php if ( $has_slots ) : ?>
                <div class="dsd-field-group dsd-slots-group" id="slotora-slots-group" style="display:none;">
                    <label class="dsd-label">
                        <?php echo esc_html( $time_label ); ?>
                        <?php if ( $required ) : ?><span class="dsd-required" aria-label="required">*</span><?php endif; ?>
                    </label>
                    <div class="dsd-slots-loading" id="slotora-slots-loading" style="display:none;">
                        <span class="dsd-spinner"></span>
                        <span><?php esc_html_e( 'Loading slots…', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-slots-grid" id="slotora-slots-grid"></div>
                    <input type="hidden" name="slotora_delivery_slot" id="slotora_delivery_slot" value="" />
                </div>
                <?php endif; ?>

            </div><!-- .dsd-fields-wrap -->

            <div class="dsd-validation-msg" id="slotora-validation-msg" style="display:none;" role="alert"></div>

        </div><!-- .dsd-delivery-picker -->
        <?php
    }

    /**
     * Server-side validation on checkout submit.
     */
    public function validate_selection() {
        if ( ! Slotora_Settings::is_enabled() ) return;

        $required  = Slotora_Settings::is_required();
        $has_slots = count( Slotora_Settings::get_time_slots() ) > 0;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by WooCommerce checkout process; value sanitized via Slotora_Helpers::sanitize_date().
        $date = isset( $_POST['slotora_delivery_date'] ) ? Slotora_Helpers::sanitize_date( wp_unslash( $_POST['slotora_delivery_date'] ) ) : false;

        if ( $required && ! $date ) {
            wc_add_notice(
                __( 'Please select a delivery date before placing your order.', 'slotora-delivery-scheduler' ),
                'error'
            );
            return;
        }

        if ( $date ) {
            // Verify date is still available
            $available = Slotora_Helpers::get_available_dates();
            if ( ! in_array( $date, $available, true ) ) {
                wc_add_notice(
                    __( 'The selected delivery date is no longer available. Please choose another date.', 'slotora-delivery-scheduler' ),
                    'error'
                );
                return;
            }

            if ( $has_slots ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce checkout process.
                $slot = isset( $_POST['slotora_delivery_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slotora_delivery_slot'] ) ) : '';

                if ( $required && empty( $slot ) ) {
                    wc_add_notice(
                        __( 'Please select a delivery time slot before placing your order.', 'slotora-delivery-scheduler' ),
                        'error'
                    );
                    return;
                }

                if ( ! empty( $slot ) ) {
                    // Check slot capacity
                    $slots_for_date = Slotora_Helpers::get_slots_for_date( $date );
                    foreach ( $slots_for_date as $s ) {
                        if ( $s['value'] === $slot && $s['full'] ) {
                            wc_add_notice(
                                __( 'The selected time slot is now fully booked. Please choose another.', 'slotora-delivery-scheduler' ),
                                'error'
                            );
                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Save delivery date and slot to order meta.
     *
     * @param WC_Order $order
     */
    public function save_to_order( $order ) {
        if ( ! Slotora_Settings::is_enabled() ) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by WooCommerce checkout process; value sanitized via Slotora_Helpers::sanitize_date().
        $date = isset( $_POST['slotora_delivery_date'] ) ? Slotora_Helpers::sanitize_date( wp_unslash( $_POST['slotora_delivery_date'] ) ) : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce checkout process.
        $slot = isset( $_POST['slotora_delivery_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slotora_delivery_slot'] ) ) : '';

        if ( $date ) {
            $order->update_meta_data( '_slotora_delivery_date', $date );
        }

        if ( $slot ) {
            $order->update_meta_data( '_slotora_delivery_slot', $slot );

            // Find slot label
            $slots = Slotora_Settings::get_time_slots();
            foreach ( $slots as $s ) {
                if ( $s['value'] === $slot ) {
                    $order->update_meta_data( '_slotora_delivery_slot_label', $s['label'] );
                    break;
                }
            }
        }

        $order->save();

        // Record booking in DB
        if ( $date ) {
            Slotora_Helpers::save_booking( $order->get_id(), $date, $slot );
            do_action( 'slotora_after_booking_saved', $order->get_id(), $date, $slot );
        }
    }
}
