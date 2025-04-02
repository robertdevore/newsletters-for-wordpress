<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom list table class for managing newsletter subscribers in the WordPress admin.
 *
 * Extends WP_List_Table to display subscriber data (name, email, signup time, and URL)
 * with support for search, pagination, and a delete action.
 *
 * @since 1.0.0
 */
class Subscribers_List_Table extends WP_List_Table {

    /**
     * Constructs a new instance of the Subscribers_List_Table class.
     *
     * Sets up default labels for singular and plural forms, as well as whether the table supports AJAX.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct( [
            'singular' => esc_html__( 'Subscriber', 'newsletters-wp' ),
            'plural'   => esc_html__( 'Subscribers', 'newsletters-wp' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Prepares and populates the table with data from the newsletter subscribers table.
     *
     * Handles pagination, optional searching by email or name, and sets up columns for display.
     *
     * @since 1.0.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsletter_subscribers';

        // Set the column headers before anything else.
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = []; 
        // If you had columns you want to make sortable, you'd fill $sortable accordingly.
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        // Handle pagination.
        $per_page     = 10;
        $current_page = $this->get_pagenum();

        // Handle search.
        $search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
        $where  = '';
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where = $wpdb->prepare( " WHERE email LIKE %s OR name LIKE %s", $like, $like );
        }

        // Count total items.
        $total_query = "SELECT COUNT(*) FROM $table_name $where";
        $total_items = (int) $wpdb->get_var( $total_query );

        // Grab the actual rows.
        $offset      = ( $current_page - 1 ) * $per_page;
        $data_query  = "SELECT * FROM $table_name $where";
        $data_query .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

        // Get rows.
        $this->items = $wpdb->get_results( $data_query, ARRAY_A );

        // Set the pagination args.
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    /**
     * Retrieves the columns of the subscribers table.
     *
     * Defines columns for the ID, name, email, signup time, and signup URL.
     *
     * @since  1.0.0
     * @return array An associative array of column slugs and their labels.
     */
    public function get_columns() {
        return [
            'id'          => esc_html__( 'ID', 'newsletters-wp' ),
            'name'        => esc_html__( 'Name', 'newsletters-wp' ),
            'email'       => esc_html__( 'Email', 'newsletters-wp' ),
            'signup_time' => esc_html__( 'Signup Time', 'newsletters-wp' ),
            'signup_url'  => esc_html__( 'Signup URL', 'newsletters-wp' ),
        ];
    }

    /**
     * Renders the column for subscriber names, including row actions (e.g., delete).
     *
     * Creates a nonce and link for deleting the subscriber, and returns the
     * subscriber's name along with the "Delete" action link.
     *
     * @param array $item The current subscriber data.
     * 
     * @since  1.0.0
     * @return string The subscriber name along with possible row actions.
     */
    protected function column_name( $item ) {
        // Create a nonce specific to deleting this subscriber.
        $delete_nonce = wp_create_nonce( 'delete_subscriber_' . $item['id'] );
    
        // Build the "delete" URL.
        $delete_url = add_query_arg( [
            'page'       => 'newsletter-signups',
            'action'     => 'delete',
            'subscriber' => $item['id'],
            '_wpnonce'   => $delete_nonce,
        ], admin_url( 'admin.php' ) );
    
        // Build row actions.
        $actions = [
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'Are you sure you want to delete this subscriber?\');">%s</a>',
                esc_url( $delete_url ),
                esc_html__( 'Delete', 'newsletters-wp' )
            ),
        ];
    
        // Return the name plus our row actions.
        return sprintf(
            '%1$s %2$s',
            esc_html( $item['name'] ),
            $this->row_actions( $actions )
        );
    }

    /**
     * The default callback for columns that are not otherwise handled.
     *
     * Returns the value from the $item array for the given column, or an empty string if not set.
     *
     * @param array  $item         The current subscriber data.
     * @param string $column_name  The name of the column to display.
     * 
     * @since  1.0.0
     * @return string The column value to display.
     */
    protected function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
    }
}
