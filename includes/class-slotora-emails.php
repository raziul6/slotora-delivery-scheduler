<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Emails {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Schedule cron for reminder emails
        if ( get_option( 'slotora_reminder_email', 'no' ) === 'yes' ) {
            add_action( 'init', array( $this, 'maybe_schedule_cron' ) );
            add_action( 'slotora_send_reminders', array( $this, 'send_reminders' ) );
        }
    }

    public function maybe_schedule_cron() {
        if ( ! wp_next_scheduled( 'slotora_send_reminders' ) ) {
            wp_schedule_event( strtotime( 'today 08:00:00' ), 'daily', 'slotora_send_reminders' );
        }
    }

    /**
     * Send delivery reminder emails for orders delivering tomorrow.
     */
    public function send_reminders() {
        global $wpdb;

        $tomorrow = current_datetime()->modify( '+1 day' )->format( 'Y-m-d' );
        $table    = $wpdb->prefix . 'slotora_slot_bookings';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom bookings table built from $wpdb->prefix; one-off cron read, caching not applicable.
        $order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT order_id FROM {$table} WHERE slot_date = %s",
                $tomorrow
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( empty( $order_ids ) ) return;

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( absint( $order_id ) );
            if ( ! $order ) continue;

            // Only for paid/processing/on-hold orders
            if ( ! in_array( $order->get_status(), array( 'processing', 'on-hold', 'pending' ), true ) ) {
                continue;
            }

            $info = Slotora_Orders::get_delivery_info( $order );
            if ( ! $info ) continue;

            $to      = $order->get_billing_email();
            $name    = $order->get_billing_first_name();
            $subject = sprintf(
                /* translators: %s: store name */
                __( 'Your delivery from %s is tomorrow!', 'slotora-delivery-scheduler' ),
                get_bloginfo( 'name' )
            );

            $message = $this->get_reminder_email_html( $order, $info, $name );

            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
            );

            wp_mail( $to, $subject, $message, $headers );
        }
    }

    /**
     * Build reminder email HTML.
     */
    private function get_reminder_email_html( $order, $info, $name ) {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f5;margin:0;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#2563eb;padding:28px 36px;">
    <h1 style="color:#fff;margin:0;font-size:22px;"><?php esc_html_e( 'Your delivery is tomorrow!', 'slotora-delivery-scheduler' ); ?></h1>
  </td></tr>
  <tr><td style="padding:32px 36px;">
    <p style="margin:0 0 16px;font-size:16px;color:#374151;">
      <?php
        /* translators: %s: customer first name */
        echo esc_html( sprintf( __( 'Hi %s,', 'slotora-delivery-scheduler' ), $name ) );
      ?>
    </p>
    <p style="margin:0 0 24px;font-size:15px;color:#6b7280;"><?php esc_html_e( 'Just a quick reminder that your order is scheduled for delivery tomorrow:', 'slotora-delivery-scheduler' ); ?></p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1px solid #dbeafe;border-radius:8px;margin-bottom:24px;">
      <tr>
        <td style="padding:16px 20px;border-bottom:1px solid #dbeafe;">
          <strong style="display:block;font-size:12px;color:#93c5fd;text-transform:uppercase;letter-spacing:.05em;"><?php esc_html_e( 'Delivery Date', 'slotora-delivery-scheduler' ); ?></strong>
          <span style="font-size:18px;font-weight:600;color:#1e40af;"><?php echo esc_html( $info['date_formatted'] ); ?></span>
        </td>
      </tr>
      <?php if ( ! empty( $info['slot_label'] ) ) : ?>
      <tr>
        <td style="padding:16px 20px;">
          <strong style="display:block;font-size:12px;color:#93c5fd;text-transform:uppercase;letter-spacing:.05em;"><?php esc_html_e( 'Time Slot', 'slotora-delivery-scheduler' ); ?></strong>
          <span style="font-size:18px;font-weight:600;color:#1e40af;"><?php echo esc_html( $info['slot_label'] ); ?></span>
        </td>
      </tr>
      <?php endif; ?>
    </table>
    <p style="font-size:14px;color:#9ca3af;">
      <?php
        /* translators: %s: order number */
        echo esc_html( sprintf( __( 'Order #%s', 'slotora-delivery-scheduler' ), $order->get_order_number() ) );
      ?>
    </p>
  </td></tr>
  <tr><td style="padding:20px 36px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:13px;color:#9ca3af;text-align:center;">
    <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
  </td></tr>
</table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
