<?php
defined( 'ABSPATH' ) || exit;

class Slotora_Admin_Orders {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Order list column
        add_filter( 'manage_woocommerce_page_wc-orders_columns',   array( $this, 'add_column' ) );
        add_filter( 'manage_edit-shop_order_columns',              array( $this, 'add_column' ) );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_action( 'manage_shop_order_posts_custom_column',           array( $this, 'render_column_legacy' ), 10, 2 );

        // Order details meta box
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    }

    public function add_column( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'order_status' ) {
                $new['slotora_delivery'] = __( 'Delivery', 'slotora-delivery-scheduler' );
            }
        }
        return $new;
    }

    public function render_column( $column, $order ) {
        if ( $column !== 'slotora_delivery' ) return;
        $info = Slotora_Orders::get_delivery_info( $order );
        if ( $info ) {
            echo '<span class="dsd-order-col-date">' . esc_html( $info['date_formatted'] ) . '</span>';
            if ( ! empty( $info['slot_label'] ) ) {
                echo '<br><small class="dsd-order-col-slot">' . esc_html( $info['slot_label'] ) . '</small>';
            }
        } else {
            echo '—';
        }
    }

    public function render_column_legacy( $column, $post_id ) {
        if ( $column !== 'slotora_delivery' ) return;
        $order = wc_get_order( $post_id );
        if ( $order ) $this->render_column( $column, $order );
    }

    public function add_meta_box() {
        $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'slotora_delivery_info',
            __( 'Delivery Schedule', 'slotora-delivery-scheduler' ),
            array( $this, 'render_meta_box' ),
            $screen,
            'side',
            'high'
        );
    }

    public function render_meta_box( $post_or_order ) {
        $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
        if ( ! $order ) return;

        $info = Slotora_Orders::get_delivery_info( $order );
        ?>
        <div class="dsd-admin-meta-box">
        <?php if ( $info ) : ?>
            <div class="dsd-mb-row">
                <span class="dsd-mb-label"><?php esc_html_e( 'Date', 'slotora-delivery-scheduler' ); ?></span>
                <span class="dsd-mb-val"><?php echo esc_html( $info['date_formatted'] ); ?></span>
            </div>
            <?php if ( ! empty( $info['slot_label'] ) ) : ?>
            <div class="dsd-mb-row">
                <span class="dsd-mb-label"><?php esc_html_e( 'Time Slot', 'slotora-delivery-scheduler' ); ?></span>
                <span class="dsd-mb-val"><?php echo esc_html( $info['slot_label'] ); ?></span>
            </div>
            <?php endif; ?>
        <?php else : ?>
            <p class="dsd-mb-none"><?php esc_html_e( 'No delivery date selected.', 'slotora-delivery-scheduler' ); ?></p>
        <?php endif; ?>
        </div>
        <?php
    }
}
