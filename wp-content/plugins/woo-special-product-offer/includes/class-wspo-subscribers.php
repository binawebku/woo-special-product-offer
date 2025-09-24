<?php
/**
 * Admin subscriber reporting for Woo Special Product Offer.
 *
 * @package Woo_Special_Product_Offer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( is_admin() && ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WSPO_Subscribers' ) ) {

    /**
     * Provides a lightweight subscriber list and export inside the admin area.
     */
    class WSPO_Subscribers {

        /**
         * Singleton instance.
         *
         * @var WSPO_Subscribers|null
         */
        protected static $instance = null;

        /**
         * Menu hook suffix for the admin screen.
         *
         * @var string
         */
        protected $menu_hook = '';

        /**
         * Retrieve the subscriber controller instance.
         *
         * @return WSPO_Subscribers
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Register admin hooks.
         */
        protected function __construct() {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_node' ), 100 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'admin_post_wspo_export_subscribers', array( $this, 'export_subscribers' ) );
            add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
        }

        /**
         * Register the top level admin menu.
         */
        public function register_menu() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            $this->menu_hook = add_menu_page(
                __( 'WSPO Subscribers', 'woo-special-product-offer' ),
                __( 'WSPO Subscribers', 'woo-special-product-offer' ),
                'manage_woocommerce',
                'wspo-subscribers',
                array( $this, 'render_page' ),
                'dashicons-groups',
                56
            );

            if ( $this->menu_hook ) {
                add_action( 'load-' . $this->menu_hook, array( $this, 'register_screen_options' ) );
            }
        }

        /**
         * Configure screen options for the list table.
         */
        public function register_screen_options() {
            add_screen_option(
                'per_page',
                array(
                    'label'   => __( 'Subscribers per page', 'woo-special-product-offer' ),
                    'default' => 20,
                    'option'  => 'wspo_subscribers_per_page',
                )
            );
        }

        /**
         * Persist the screen option value.
         *
         * @param mixed  $status Default status.
         * @param string $option Option name.
         * @param int    $value  Submitted value.
         * @return mixed
         */
        public function set_screen_option( $status, $option, $value ) {
            if ( 'wspo_subscribers_per_page' === $option ) {
                return max( 1, (int) $value );
            }

            return $status;
        }

        /**
         * Register a quick access node in the admin bar.
         *
         * @param WP_Admin_Bar $admin_bar Admin bar instance.
         */
        public function register_admin_bar_node( $admin_bar ) {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            if ( ! class_exists( 'WP_Admin_Bar' ) ) {
                return;
            }

            if ( ! $admin_bar instanceof WP_Admin_Bar ) {
                return;
            }

            $admin_bar->add_node(
                array(
                    'id'    => 'wspo-subscribers',
                    'title' => __( 'WSPO Subscribers', 'woo-special-product-offer' ),
                    'href'  => admin_url( 'admin.php?page=wspo-subscribers' ),
                )
            );
        }

        /**
         * Enqueue styles for the admin screen when required.
         *
         * @param string $hook_suffix Current admin page hook.
         */
        public function enqueue_assets( $hook_suffix ) {
            if ( $this->menu_hook !== $hook_suffix ) {
                return;
            }

            wp_enqueue_style(
                'wspo-subscribers',
                WSPO_PLUGIN_URL . 'assets/css/wspo-subscribers.css',
                array(),
                WSPO_VERSION
            );
        }

        /**
         * Render the subscriber report page.
         */
        public function render_page() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            if ( ! class_exists( 'WC_Order_Query' ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce must be active to view subscriber orders.', 'woo-special-product-offer' ) . '</p></div>';
                return;
            }

            $table = new WSPO_Subscriber_List_Table();
            $table->prepare_items();
            ?>
            <div class="wrap ws-special-offer-subscribers">
                <h1><?php esc_html_e( 'WSPO Subscribers', 'woo-special-product-offer' ); ?></h1>
                <p class="description"><?php esc_html_e( 'Review orders that captured a subscription phone number and export the list for follow-up campaigns.', 'woo-special-product-offer' ); ?></p>

                <div class="wspo-subscribers-actions">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="wspo_export_subscribers" />
                        <input type="hidden" name="orderby" value="<?php echo esc_attr( $table->get_orderby_value() ); ?>" />
                        <input type="hidden" name="order" value="<?php echo esc_attr( $table->get_order_value() ); ?>" />
                        <?php wp_nonce_field( 'wspo_export_subscribers', '_wspo_export_nonce', false ); ?>
                        <?php submit_button( __( 'Export CSV', 'woo-special-product-offer' ), 'secondary', 'wspo-export-subscribers', false ); ?>
                    </form>
                </div>

                <form method="get">
                    <input type="hidden" name="page" value="wspo-subscribers" />
                    <?php $table->display(); ?>
                </form>
            </div>
            <?php
        }

        /**
         * Handle CSV export requests for subscriber data.
         */
        public function export_subscribers() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( esc_html__( 'You are not allowed to export this data.', 'woo-special-product-offer' ) );
            }

            $nonce = isset( $_POST['_wspo_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wspo_export_nonce'] ) ) : '';

            if ( ! wp_verify_nonce( $nonce, 'wspo_export_subscribers' ) ) {
                wp_die( esc_html__( 'Security check failed. Please try again.', 'woo-special-product-offer' ) );
            }

            if ( ! class_exists( 'WC_Order_Query' ) ) {
                wp_die( esc_html__( 'WooCommerce must be active to export subscriber data.', 'woo-special-product-offer' ) );
            }

            $orderby = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'date';
            $order   = isset( $_POST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['order'] ) ) ) : 'DESC';
            $order   = 'ASC' === $order ? 'ASC' : 'DESC';

            $order_map = array(
                'id'   => 'ID',
                'date' => 'date',
            );

            $orderby = isset( $order_map[ $orderby ] ) ? $order_map[ $orderby ] : 'date';

            $results = self::get_subscriber_orders(
                array(
                    'limit'    => -1,
                    'paginate' => false,
                    'orderby'  => $orderby,
                    'order'    => $order,
                    'return'   => 'ids',
                )
            );

            $order_ids = is_array( $results ) ? $results : array();

            $filename = 'wspo-subscribers-' . gmdate( 'Y-m-d-His' ) . '.csv';

            nocache_headers();
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );

            $output = fopen( 'php://output', 'w' );

            fputcsv(
                $output,
                array(
                    __( 'Order ID', 'woo-special-product-offer' ),
                    __( 'Customer Name', 'woo-special-product-offer' ),
                    __( 'Customer Email', 'woo-special-product-offer' ),
                    __( 'Subscription Phone', 'woo-special-product-offer' ),
                    __( 'Order Date', 'woo-special-product-offer' ),
                )
            );

            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );

                if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
                    continue;
                }

                $name = method_exists( $order, 'get_formatted_billing_full_name' ) ? $order->get_formatted_billing_full_name() : '';

                if ( '' === $name ) {
                    $first = $order->get_billing_first_name();
                    $last  = $order->get_billing_last_name();
                    $name  = trim( $first . ' ' . $last );
                }

                if ( '' === $name ) {
                    $name = __( 'Guest customer', 'woo-special-product-offer' );
                }

                $email = $order->get_billing_email();
                $phone = $order->get_meta( 'wspo_subscription_phone', true );
                $date  = $order->get_date_created();
                $date  = $date ? wc_format_datetime( $date ) : '';

                fputcsv(
                    $output,
                    array(
                        '#' . $order->get_id(),
                        $name,
                        $email,
                        $phone,
                        $date,
                    )
                );
            }

            fclose( $output );

            exit;
        }

        /**
         * Run a subscriber-focused order query.
         *
         * @param array $args Query arguments.
         * @return array
         */
        public static function get_subscriber_orders( $args = array() ) {
            if ( ! class_exists( 'WC_Order_Query' ) ) {
                return array();
            }

            $defaults = array(
                'limit'    => 20,
                'page'     => 1,
                'paginate' => false,
                'return'   => 'ids',
                'orderby'  => 'date',
                'order'    => 'DESC',
                'type'     => 'shop_order',
            );

            $query_args = wp_parse_args( $args, $defaults );

            if ( function_exists( 'wc_get_order_statuses' ) && empty( $query_args['status'] ) ) {
                $query_args['status'] = array_keys( wc_get_order_statuses() );
            }

            $using_hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
                && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

            if ( $using_hpos ) {
                global $wpdb;

                $limit  = isset( $query_args['limit'] ) ? (int) $query_args['limit'] : 20;
                $page   = max( 1, (int) $query_args['page'] );
                $offset = ( $limit > 0 ) ? ( ( $page - 1 ) * $limit ) : 0;

                $order   = ( 'ASC' === strtoupper( $query_args['order'] ) ) ? 'ASC' : 'DESC';
                $orderby = strtolower( $query_args['orderby'] );

                switch ( $orderby ) {
                    case 'id':
                        $orderby_clause = 'o.id';
                        break;
                    case 'date':
                    default:
                        $orderby_clause = 'o.date_created_gmt';
                        break;
                }

                $types = array();

                if ( ! empty( $query_args['type'] ) ) {
                    $types = array_map( 'sanitize_key', (array) $query_args['type'] );
                }

                $statuses = array();

                if ( ! empty( $query_args['status'] ) ) {
                    $statuses = array_map(
                        static function( $status ) {
                            $status = sanitize_key( $status );

                            if ( 0 === strpos( $status, 'wc-' ) ) {
                                return $status;
                            }

                            return 'wc-' . $status;
                        },
                        (array) $query_args['status']
                    );
                }

                $conditions = array(
                    'om.meta_key = %s',
                    "om.meta_value <> ''",
                );

                $params = array( 'wspo_subscription_phone' );

                if ( ! empty( $types ) ) {
                    $conditions[] = 'o.type IN (' . implode( ', ', array_fill( 0, count( $types ), '%s' ) ) . ')';
                    $params       = array_merge( $params, $types );
                }

                if ( ! empty( $statuses ) ) {
                    $conditions[] = 'o.status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ')';
                    $params       = array_merge( $params, $statuses );
                }

                $where_sql         = 'WHERE ' . implode( ' AND ', $conditions );
                $orders_table      = $wpdb->prefix . 'wc_orders';
                $orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
                $base_sql          = "FROM {$orders_table} o INNER JOIN {$orders_meta_table} om ON o.id = om.order_id {$where_sql}";
                $select_sql        = "SELECT DISTINCT o.id {$base_sql} ORDER BY {$orderby_clause} {$order}";
                $select_params     = $params;

                if ( $limit > 0 ) {
                    $select_sql    .= ' LIMIT %d OFFSET %d';
                    $select_params[] = $limit;
                    $select_params[] = $offset;
                }

                $prepared_select = $wpdb->prepare( $select_sql, $select_params );
                $order_ids       = $prepared_select ? $wpdb->get_col( $prepared_select ) : array();

                $order_ids = array_map( 'absint', (array) $order_ids );

                $return_type = isset( $query_args['return'] ) ? $query_args['return'] : 'ids';
                $return_type = is_string( $return_type ) ? strtolower( $return_type ) : 'ids';

                if ( 'objects' === $return_type ) {
                    $orders = array_filter( array_map( 'wc_get_order', $order_ids ) );
                } else {
                    $orders = $order_ids;
                }

                if ( ! empty( $query_args['paginate'] ) ) {
                    $prepared_count = $wpdb->prepare( "SELECT COUNT( DISTINCT o.id ) {$base_sql}", $params );
                    $total          = $prepared_count ? (int) $wpdb->get_var( $prepared_count ) : 0;
                    $max_pages      = 1;

                    if ( $limit > 0 ) {
                        $max_pages = max( 1, (int) ceil( $total / $limit ) );
                    }

                    return array(
                        'orders'        => array_values( $orders ),
                        'total'         => $total,
                        'max_num_pages' => $max_pages,
                    );
                }

                return array_values( $orders );
            }

            $meta_query = array();

            if ( ! empty( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
                $meta_query = $query_args['meta_query'];
            }

            $meta_query[] = array(
                'key'     => 'wspo_subscription_phone',
                'value'   => '',
                'compare' => '!=',
            );

            $query_args['meta_query'] = $meta_query;

            $query = new WC_Order_Query( $query_args );

            return $query->get_orders();
        }
    }
}

if ( ! class_exists( 'WSPO_Subscriber_List_Table' ) ) {

    /**
     * List table renderer for subscriber orders.
     */
    class WSPO_Subscriber_List_Table extends WP_List_Table {

        /**
         * Current orderby value.
         *
         * @var string
         */
        protected $orderby = 'date';

        /**
         * Current order direction.
         *
         * @var string
         */
        protected $order = 'DESC';

        /**
         * Prepare items for display.
         */
        public function prepare_items() {
            $columns  = $this->get_columns();
            $hidden   = array();
            $sortable = $this->get_sortable_columns();

            $this->_column_headers = array( $columns, $hidden, $sortable );

            $per_page     = $this->get_items_per_page( 'wspo_subscribers_per_page', 20 );
            $current_page = max( 1, $this->get_pagenum() );

            $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date';
            $order   = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';

            $this->orderby = array_key_exists( $orderby, $sortable ) ? $orderby : 'date';
            $this->order   = 'ASC' === $order ? 'ASC' : 'DESC';

            $order_map = array(
                'id'   => 'ID',
                'date' => 'date',
            );

            $query_orderby = isset( $order_map[ $this->orderby ] ) ? $order_map[ $this->orderby ] : 'date';

            $results = WSPO_Subscribers::get_subscriber_orders(
                array(
                    'limit'    => $per_page,
                    'page'     => $current_page,
                    'paginate' => true,
                    'orderby'  => $query_orderby,
                    'order'    => $this->order,
                    'return'   => 'ids',
                )
            );

            $order_ids   = array();
            $total_items = 0;

            if ( isset( $results['orders'] ) && is_array( $results['orders'] ) ) {
                $order_ids   = $results['orders'];
                $total_items = isset( $results['total'] ) ? (int) $results['total'] : count( $order_ids );
            } elseif ( is_array( $results ) ) {
                $order_ids   = $results;
                $total_items = count( $order_ids );
            }

            $items = array();

            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );

                if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
                    continue;
                }

                $name = method_exists( $order, 'get_formatted_billing_full_name' ) ? $order->get_formatted_billing_full_name() : '';

                if ( '' === $name ) {
                    $first = $order->get_billing_first_name();
                    $last  = $order->get_billing_last_name();
                    $name  = trim( $first . ' ' . $last );
                }

                if ( '' === $name ) {
                    $name = __( 'Guest customer', 'woo-special-product-offer' );
                }

                $email = $order->get_billing_email();
                $phone = $order->get_meta( 'wspo_subscription_phone', true );
                $date  = $order->get_date_created();
                $date  = $date ? wc_format_datetime( $date ) : '';

                $items[] = array(
                    'order_id' => $order->get_id(),
                    'customer' => $name,
                    'email'    => $email,
                    'phone'    => $phone,
                    'date'     => $date,
                );
            }

            $this->items = $items;

            $total_pages = $per_page > 0 ? ceil( $total_items / $per_page ) : 0;

            $this->set_pagination_args(
                array(
                    'total_items' => $total_items,
                    'per_page'    => $per_page,
                    'total_pages' => max( 1, $total_pages ),
                )
            );
        }

        /**
         * Retrieve the columns for the table.
         *
         * @return array
         */
        public function get_columns() {
            return array(
                'order_id' => __( 'Order ID', 'woo-special-product-offer' ),
                'customer' => __( 'Customer', 'woo-special-product-offer' ),
                'email'    => __( 'Email', 'woo-special-product-offer' ),
                'phone'    => __( 'Subscription Phone', 'woo-special-product-offer' ),
                'date'     => __( 'Order Date', 'woo-special-product-offer' ),
            );
        }

        /**
         * Mark sortable columns.
         *
         * @return array
         */
        protected function get_sortable_columns() {
            return array(
                'order_id' => array( 'id', false ),
                'date'     => array( 'date', true ),
            );
        }

        /**
         * Render default column output.
         *
         * @param array  $item        Current row.
         * @param string $column_name Column identifier.
         * @return string
         */
        protected function column_default( $item, $column_name ) {
            if ( isset( $item[ $column_name ] ) ) {
                return esc_html( $item[ $column_name ] );
            }

            return '';
        }

        /**
         * Render order ID column with edit link.
         *
         * @param array $item Current row.
         * @return string
         */
        protected function column_order_id( $item ) {
            $link = admin_url( 'post.php?post=' . absint( $item['order_id'] ) . '&action=edit' );

            return sprintf( '<a href="%1$s">#%2$s</a>', esc_url( $link ), esc_html( $item['order_id'] ) );
        }

        /**
         * Message to display when no items are found.
         */
        public function no_items() {
            esc_html_e( 'No subscriber orders found yet.', 'woo-special-product-offer' );
        }

        /**
         * Get the current orderby value.
         *
         * @return string
         */
        public function get_orderby_value() {
            return $this->orderby;
        }

        /**
         * Get the current order direction.
         *
         * @return string
         */
        public function get_order_value() {
            return $this->order;
        }
    }
}

