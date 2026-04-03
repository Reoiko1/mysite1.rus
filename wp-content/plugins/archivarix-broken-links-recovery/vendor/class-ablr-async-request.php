<?php
/**
 * WP Async Request
 *
 * Based on WP Background Processing by deliciousbrains.
 * @see https://github.com/deliciousbrains/wp-background-processing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class ABLR_Async_Request {

    protected $prefix = 'ablr';
    protected $action = 'async_request';
    protected $identifier;
    protected $data = array();

    public function __construct() {
        $this->identifier = $this->prefix . '_' . $this->action;
        add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
        add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
    }

    public function data( $data ) {
        $this->data = $data;
        return $this;
    }

    public function dispatch() {
        $url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
        $args = $this->get_post_args();
        $result = wp_remote_post( esc_url_raw( $url ), $args );

        // If loopback request failed, schedule a cron fallback so the queue still gets processed.
        // Note: cron_hook_identifier is defined in child class ABLR_Background_Process.
        if ( is_wp_error( $result ) && ! empty( $this->cron_hook_identifier ) ) {
            if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
                wp_schedule_single_event( time() + 10, $this->cron_hook_identifier );
            }
        }

        return $result;
    }

    protected function get_query_args() {
        return array(
            'action' => $this->identifier,
            'nonce'  => wp_create_nonce( $this->identifier ),
        );
    }

    protected function get_query_url() {
        return admin_url( 'admin-ajax.php' );
    }

    protected function get_post_args() {
        $args = array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'body'      => $this->data,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        );

        // Include cookies when available (AJAX context). In cron context, $_COOKIE may be empty.
        if ( ! empty( $_COOKIE ) ) {
            $args['cookies'] = $_COOKIE;
        }

        return $args;
    }

    public function maybe_handle() {
        session_write_close();

        check_ajax_referer( $this->identifier, 'nonce' );

        $this->handle();
        wp_die();
    }

    abstract protected function handle();
}
