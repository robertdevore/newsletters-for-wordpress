<?php

/**
 * The plugin bootstrap file.
 *
 * @link              https://
 * @since             1.0.0
 * @package           Newsletters_For_WordPress
 *
 * @wordpress-plugin
 *
 * Plugin Name: Newsletters for WordPress®
 * Description: A plugin to manage newsletter signups and store subscriber data in a custom table.
 * Plugin URI:  https://github.com/robertdevore/newsletters-for-wordpress/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: newsletters-wp
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/newsletters-for-wordpress/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define the plugin version number.
define( 'NWP_VERSION', '1.0.0' );

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/newsletters-for-wordpress/',
	__FILE__,
	'newsletters-wp'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

// Newsletters List Table for the admin.
require 'classes/NewslettersListTable.php';

// Plugin basename.
$plugin_name = plugin_basename( __FILE__ );

/**
 * Add settings link on plugin page
 *
 * @param array $links an array of links related to the plugin.
 *
 * @since  1.0.0
 * @return array updatead array of links related to the plugin.
 */
function newsletters_wp_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=newsletter-signups">' . esc_html__( 'Settings', 'newsletters-wp' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( "plugin_action_links_$plugin_name", 'newsletters_wp_settings_link' );

/**
 * Load plugin text domain for translations
 * 
 * @since  1.0.0
 * @return void
 */
function newsletters_wp_load_textdomain() {
    load_plugin_textdomain( 
        'newsletters-wp', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'newsletters_wp_load_textdomain' );

/**
 * Creates a custom database table for newsletter subscribers on plugin activation.
 *
 * This function is hooked to the plugin’s activation hook and uses dbDelta() 
 * to create (or upgrade) a table for storing subscriber details. The table 
 * includes columns for email, name, signup time, and signup URL.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * 
 * @since  1.0.0
 * @return void
 */
function nw_create_custom_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'newsletter_subscribers';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        signup_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        signup_url TEXT DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'nw_create_custom_table' );

/**
 * Removes the custom database table for newsletter subscribers on plugin uninstall.
 *
 * This function is hooked to the plugin’s uninstall hook, ensuring that the
 * table is dropped only when the plugin is explicitly deleted through the 
 * WordPress admin.
 *
 * @since 1.0.0
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return void
 */
function nw_remove_custom_table() {
    // If called directly outside of the WordPress uninstall process, abort.
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'newsletter_subscribers';

    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
register_uninstall_hook( __FILE__, 'nw_remove_custom_table' );

/**
 * Registers and handles the newsletter signup form shortcode.
 *
 * Generates and returns the HTML for the newsletter signup form, 
 * including fields for name and email as well as a submit button.
 *
 * @since  1.0.0
 * @return string The generated HTML content for the newsletter form.
 */
function nw_newsletter_form_shortcode() {
    // Form HTML with shortcode.
    ob_start();
    ?>
    <form id="nw-newsletter-form" method="post">
        <label for="nw-name"><?php esc_html_e( 'Name', 'newsletters-wp' ); ?>:</label>
        <input type="text" id="nw-name" name="name" placeholder="<?php esc_html_e( 'Your Name', 'newsletters-wp' ); ?>" required>

        <label for="nw-email"><?php esc_html_e( 'Email', 'newsletters-wp' ); ?>:</label>
        <input type="email" id="nw-email" name="email" placeholder="<?php esc_html_e( 'Your Email', 'newsletters-wp' ); ?>" required>

        <button type="submit"><?php esc_html_e( 'Subscribe', 'newsletters-wp' ); ?></button>
    </form>
    <div id="nw-response-message" style="margin-top:10px;"></div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'newsletter_form', 'nw_newsletter_form_shortcode' );

/**
 * Handles an AJAX request to save subscriber data into the newsletter subscribers table.
 *
 * Checks the WordPress nonce for security, then sanitizes and validates the submitted
 * name, email, and URL data. If successful, inserts the subscriber data into the
 * custom newsletter subscribers table. Returns a JSON response indicating success or error.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * 
 * @since  1.0.0
 * @return void Outputs a JSON response and terminates script execution.
 */
function nw_handle_newsletter_signup() {
    // Verify nonce for security.
    check_ajax_referer( 'nw_newsletter_nonce', 'security' );

    // Get POST data.
    $name  = sanitize_text_field( $_POST['name'] );
    $email = sanitize_email( $_POST['email'] );
    $url   = sanitize_text_field( $_POST['url'] );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid email address.', 'newsletters-wp' ) ] );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'newsletter_subscribers';

    // Insert subscriber data into custom table.
    $inserted = $wpdb->insert( 
        $table_name,
        [
            'email'      => $email,
            'name'       => $name,
            'signup_url' => $url,
        ],
        [ '%s', '%s', '%s' ]
    );

    if ( false === $inserted ) {
        wp_send_json_error( [ 'message' => __( 'Error saving data.', 'newsletters-wp' ) ] );
    }

    wp_send_json_success( [ 'message' => __( 'Thank you for subscribing!', 'newsletters-wp' ) ] );
}
add_action( 'wp_ajax_nw_newsletter_signup', 'nw_handle_newsletter_signup' );
add_action( 'wp_ajax_nopriv_nw_newsletter_signup', 'nw_handle_newsletter_signup' );

/**
 * Enqueues scripts and styles for the newsletter functionality.
 *
 * This function loads the main JavaScript file for the newsletter form, along with 
 * localized data for AJAX requests, as well as the associated CSS for styling.
 *
 * @since  1.0.0
 * @return void
 */
function nw_enqueue_scripts() {
    wp_enqueue_script( 'nw-newsletter-script', plugin_dir_url( __FILE__ ) . 'js/newsletter.js', [ 'jquery' ], NWP_VERSION, true );
    wp_localize_script( 'nw-newsletter-script', 'nwAjax', [
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( 'nw_newsletter_nonce' ),
    ] );

    wp_enqueue_style( 'nw-style', plugin_dir_url( __FILE__ ) . 'css/newsletter-form.css', [], NWP_VERSION );
}
add_action( 'wp_enqueue_scripts', 'nw_enqueue_scripts' );

/**
 * Enqueues admin scripts and styles for specific plugin pages in the WordPress admin.
 *
 * Only loads scripts and styles when on the designated plugin pages to optimize performance.
 * Also localizes the JavaScript with data needed for AJAX-based actions.
 *
 * @param string $hook The current admin page hook.
 * 
 * @since  1.0.0
 * @return void
 */
function nw_enqueue_admin_scripts( $hook ) {
    // Load scripts only on plugin pages.
    if ( in_array( $hook, [ 'toplevel_page_newsletter-signups', 'newsletter-signups_page_newsletter-send-emails' ] ) ) {
        wp_enqueue_script( 'nw-admin-script', plugin_dir_url( __FILE__ ) . 'js/admin.js', [ 'jquery' ], NWP_VERSION, true );
        wp_localize_script( 'nw-admin-script', 'nwAdminAjax', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'security' => wp_create_nonce( 'nw_admin_nonce' ),
        ] );

        wp_enqueue_style( 'nw-admin-style', plugin_dir_url( __FILE__ ) . 'css/admin.css', [], NWP_VERSION );
    }
}
add_action( 'admin_enqueue_scripts', 'nw_enqueue_admin_scripts' );

/**
 * Registers the main admin menu and a submenu page for the Newsletter plugin.
 *
 * Adds the "Newsletter Signups" top-level menu in the WordPress admin, along with 
 * a "Send Emails" submenu. Each menu item links to a corresponding function that 
 * handles the display logic for that page.
 *
 * @since  1.0.0
 * @return void
 */
function nw_register_admin_menu() {
    // Add the top-level "Newsletters" menu.
    add_menu_page(
        esc_html__( 'Newsletters', 'newsletters-wp' ),
        esc_html__( 'Newsletters', 'newsletters-wp' ),
        'manage_options',
        'newsletter-signups',
        'nw_display_admin_page',
        'dashicons-email',
        20
    );

    // Override the first sub-menu name to be "Signups"
    add_submenu_page(
        'newsletter-signups',
        esc_html__( 'Signups', 'newsletters-wp' ),
        esc_html__( 'Signups', 'newsletters-wp' ),
        'manage_options',
        'newsletter-signups',
        'nw_display_admin_page'
    );

    // 3) Add your "Send Emails" sub-menu.
    add_submenu_page(
        'newsletter-signups',
        esc_html__( 'Send Emails', 'newsletters-wp' ),
        esc_html__( 'Send Emails', 'newsletters-wp' ),
        'manage_options',
        'newsletter-send-emails',
        'nw_display_email_page'
    );
}
add_action( 'admin_menu', 'nw_register_admin_menu' );

/**
 * Renders the main admin page for viewing and managing newsletter signups.
 *
 * Instantiates and prepares a custom subscribers list table, then outputs
 * its interface within a WordPress admin page. Also includes a modal 
 * interface for adding new subscribers.
 *
 * @since  1.0.0
 * @return void
 */
function nw_display_admin_page() {
    // First, handle deletions if requested.
    nw_maybe_delete_subscriber();

    $list_table = new Subscribers_List_Table();
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e( 'Newsletter Signups', 'newsletters-wp' ); ?>
            <a id="nwp-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> <?php esc_html_e( 'Support', 'newsletters-wp' ); ?>
            </a>
            <a id="nwp-docs-btn" href="https://robertdevore.com/articles/newsletters-for-wordpress/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> <?php esc_html_e( 'Documentation', 'newsletters-wp' ); ?>
            </a>
            <button id="add-subscriber-button" class="button button-primary" style="margin-left: 20px;"><?php esc_html_e( 'Add Subscriber', 'newsletters-wp' ); ?></button>
        </h1>
        <form method="get">
            <input type="hidden" name="page" value="newsletter-signups">
            <?php
            $list_table->search_box( esc_html__( 'Search', 'newsletters-wp' ), 'search' );
            $list_table->display();
            ?>
        </form>

        <div id="add-subscriber-modal" style="display: none;">
            <div class="modal-content" style="background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; margin: 100px auto;">
                <h2><?php esc_html_e( 'Add Subscriber', 'newsletters-wp' ); ?></h2>
                <form id="add-subscriber-form">
                    <label for="subscriber-name"><?php esc_html_e( 'Name', 'newsletters-wp' ); ?>:</label>
                    <input type="text" id="subscriber-name" name="name" class="regular-text" required>

                    <label for="subscriber-email"><?php esc_html_e( 'Email', 'newsletters-wp' ); ?>:</label>
                    <input type="email" id="subscriber-email" name="email" class="regular-text" required>

                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'newsletters-wp' ); ?></button>
                    <button type="button" id="close-modal-button" class="button"><?php esc_html_e( 'Cancel', 'newsletters-wp' ); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Checks and processes a request to delete a subscriber from the database.
 *
 * Validates the request via a nonce check before deleting the specified subscriber
 * from the newsletter table. Redirects afterwards to prevent duplicate requests
 * upon page refresh.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * 
 * @since  1.0.0
 * @return void
 */
function nw_maybe_delete_subscriber() {
    // Check if we're trying to delete a subscriber.
    if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) {
        // Security check.
        $subscriber_id = absint( $_GET['subscriber'] );
        $nonce         = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

        if ( ! $subscriber_id || ! $nonce ) {
            return;
        }

        // Verify the nonce matches the one we created in column_name().
        if ( ! wp_verify_nonce( $nonce, 'delete_subscriber_' . $subscriber_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'newsletters-wp' ) );
        }

        // If valid, go ahead and delete.
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsletter_subscribers';
        $wpdb->delete( $table_name, [ 'id' => $subscriber_id ], [ '%d' ] );

        // Redirect back to avoid re-triggering delete on page refresh.
        wp_safe_redirect(
            remove_query_arg( [ 'action', 'subscriber', '_wpnonce' ], wp_get_referer() )
        );
        exit;
    }
}

/**
 * Handles the AJAX request to add a new subscriber.
 *
 * This function validates and sanitizes input, inserts the subscriber into
 * the newsletter subscribers table, and returns a JSON response indicating
 * success or failure.
 *
 * @since 1.0.0
 * @return void
 */
function nw_add_subscriber() {
    check_ajax_referer( 'nw_admin_nonce', 'security' );

    global $wpdb;
    $name  = sanitize_text_field( $_POST['name'] );
    $email = sanitize_email( $_POST['email'] );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid email address.', 'newsletters-wp' ) ] );
    }

    $table_name = $wpdb->prefix . 'newsletter_subscribers';
    $result     = $wpdb->insert(
        $table_name,
        [
            'name'  => $name,
            'email' => $email,
        ],
        [ '%s', '%s' ]
    );

    if ( $result ) {
        wp_send_json_success( [ 'message' => __( 'Subscriber added successfully.', 'newsletters-wp' ) ] );
    } else {
        wp_send_json_error( [ 'message' => __( 'Failed to add subscriber.', 'newsletters-wp' ) ] );
    }
}

add_action( 'wp_ajax_nw_add_subscriber', 'nw_add_subscriber' );

/**
 * Renders the admin page for composing and sending emails to subscribers.
 *
 * Displays a form for entering an email subject and body. If the form is submitted,
 * calls the function to send emails to all subscribers. 
 *
 * @since  1.0.0
 * @return void
 */
function nw_display_email_page() {
    if ( isset( $_POST['nw_send_email'] ) ) {
        nw_send_emails_to_subscribers();
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Send Email to Subscribers', 'newsletters-wp' ); ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nw_email_subject"><?php esc_html_e( 'Email Subject', 'newsletters-wp' ); ?></label></th>
                    <td><input type="text" id="nw_email_subject" name="nw_email_subject" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="nw_email_body"><?php esc_html_e( 'Email Body', 'newsletters-wp' ); ?></label></th>
                    <td><textarea id="nw_email_body" name="nw_email_body" class="large-text" rows="10" required></textarea></td>
                </tr>
            </table>
            <?php submit_button( __( 'Send Email', 'newsletters-wp' ), 'primary', 'nw_send_email' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Sends emails to all subscribers from the custom subscribers table.
 *
 * Retrieves all subscribers, personalizes the email body with their names, 
 * and sends each subscriber an email using wp_mail(). Finally, displays an 
 * admin notice indicating the emails have been sent successfully.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * 
 * @since  1.0.0
 * @return void
 */
function nw_send_emails_to_subscribers() {
    global $wpdb;

    $table_name  = $wpdb->prefix . 'newsletter_subscribers';
    $subscribers = $wpdb->get_results( "SELECT email, name FROM $table_name", ARRAY_A );

    $subject = sanitize_text_field( $_POST['nw_email_subject'] );
    $body    = wp_kses_post( $_POST['nw_email_body'] );
    $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

    foreach ( $subscribers as $subscriber ) {
        $to = $subscriber['email'];
        $personalized_body = str_replace( '{name}', $subscriber['name'], $body );
        wp_mail( $to, $subject, $personalized_body, $headers );
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Emails sent successfully!', 'newsletters-wp' ) . '</p></div>';
}
