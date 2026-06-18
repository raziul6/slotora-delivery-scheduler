<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Orders {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Order confirmation / thank you page + My account order view
        // woocommerce_order_details_after_order_table fires on both contexts
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_in_order_details' ) );

        // Show in order emails
        if ( get_option( 'slotora_show_in_email', 'yes' ) === 'yes' ) {
            add_action( 'woocommerce_email_order_meta', array( $this, 'display_in_email' ), 10, 3 );
        }

        // Release slot booking when order is cancelled/refunded/deleted
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'release_booking' ) );
        add_action( 'woocommerce_order_status_refunded',  array( $this, 'release_booking' ) );
        add_action( 'woocommerce_before_delete_order',    array( $this, 'release_booking' ) );
    }

    /**
     * Get delivery info for an order.
     *
     * @param  WC_Order $order
     * @return array|false  ['date' => string, 'date_formatted' => string, 'slot' => string, 'slot_label' => string]
     */
    public static function get_delivery_info( $order ) {
        $date  = $order->get_meta( '_slotora_delivery_date' );
        $slot  = $order->get_meta( '_slotora_delivery_slot' );
        $label = $order->get_meta( '_slotora_delivery_slot_label' );

        if ( empty( $date ) ) return false;

        return array(
            'date'           => $date,
            'date_formatted' => Slotora_Helpers::format_date( $date ),
            'slot'           => $slot,
            'slot_label'     => $label ?: $slot,
        );
    }

    /**
     * Display delivery info below order details table.
     *
     * @param WC_Order $order
     */
    public function display_in_order_details( $order ) {
        $info = self::get_delivery_info( $order );
        if ( ! $info ) return;
        ?>
        <section class="dsd-order-delivery-info">
            <h2><?php esc_html_e( 'Delivery Schedule', 'slotora-delivery-scheduler' ); ?></h2>
            <table class="dsd-info-table">
                <tr>
                    <th><?php esc_html_e( 'Delivery Date', 'slotora-delivery-scheduler' ); ?></th>
                    <td><?php echo esc_html( $info['date_formatted'] ); ?></td>
                </tr>
                <?php if ( ! empty( $info['slot_label'] ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Time Slot', 'slotora-delivery-scheduler' ); ?></th>
                    <td><?php echo esc_html( $info['slot_label'] ); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </section>
        <?php
    }

    /**
     * Display delivery info in order emails.
     *
     * @param WC_Order $order
     * @param bool     $sent_to_admin
     * @param bool     $plain_text
     */
    public function display_in_email( $order, $sent_to_admin, $plain_text ) {
        $info = self::get_delivery_info( $order );
        if ( ! $info ) return;

        if ( $plain_text ) {
            echo "\n" . esc_html__( 'Delivery Schedule', 'slotora-delivery-scheduler' ) . "\n";
            echo esc_html__( 'Delivery Date:', 'slotora-delivery-scheduler' ) . ' ' . esc_html( $info['date_formatted'] ) . "\n";
            if ( ! empty( $info['slot_label'] ) ) {
                echo esc_html__( 'Time Slot:', 'slotora-delivery-scheduler' ) . ' ' . esc_html( $info['slot_label'] ) . "\n";
            }
        } else {
            ?>
            <h2 style="font-size:18px;font-weight:600;margin:32px 0 12px;"><?php esc_html_e( 'Delivery Schedule', 'slotora-delivery-scheduler' ); ?></h2>
            <table cellspacing="0" cellpadding="6" border="1" style="width:100%;border:1px solid #e8e8e8;border-radius:6px;border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 14px;background:#f8f8f8;font-weight:600;border:1px solid #e8e8e8;"><?php esc_html_e( 'Delivery Date', 'slotora-delivery-scheduler' ); ?></td>
                    <td style="padding:10px 14px;border:1px solid #e8e8e8;"><?php echo esc_html( $info['date_formatted'] ); ?></td>
                </tr>
                <?php if ( ! empty( $info['slot_label'] ) ) : ?>
                <tr>
                    <td style="padding:10px 14px;background:#f8f8f8;font-weight:600;border:1px solid #e8e8e8;"><?php esc_html_e( 'Time Slot', 'slotora-delivery-scheduler' ); ?></td>
                    <td style="padding:10px 14px;border:1px solid #e8e8e8;"><?php echo esc_html( $info['slot_label'] ); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php
        }
    }

    /**
     * Free up the booking slot when an order is cancelled/refunded.
     *
     * @param int $order_id
     */
    public function release_booking( $order_id ) {
        Slotora_Helpers::remove_booking( $order_id );
    }
}
