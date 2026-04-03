<?php
/**
 * WP Background Process
 *
 * Based on WP Background Processing by deliciousbrains.
 * @see https://github.com/deliciousbrains/wp-background-processing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class ABLR_Background_Process extends ABLR_Async_Request {

    protected $action = 'background_process';
    protected $start_time = 0;
    protected $cron_hook_identifier;
    protected $cron_interval_identifier;

    public function __construct() {
        parent::__construct();

        $this->cron_hook_identifier     = $this->identifier . '_cron';
        $this->cron_interval_identifier = $this->identifier . '_cron_interval';

        add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
        add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
    }

    public function push_to_queue( $data ) {
        $this->data[] = $data;
        return $this;
    }

    public function save() {
        $key = $this->generate_key();
        if ( ! empty( $this->data ) ) {
            // Split large batches to avoid serialization issues with huge wp_options values.
            $chunk_size = 50;
            if ( count( $this->data ) > $chunk_size ) {
                $chunks = array_chunk( $this->data, $chunk_size );
                foreach ( $chunks as $chunk ) {
                    $chunk_key = $this->generate_key();
                    update_option( $chunk_key, $chunk, false );
                }
            } else {
                update_option( $key, $this->data, false );
            }
        }
        $this->data = array();
        return $this;
    }

    public function dispatch() {
        $this->schedule_event();
        return parent::dispatch();
    }

    protected function generate_key( $length = 64 ) {
        $unique  = md5( microtime() . wp_rand() );
        $prepend = $this->identifier . '_batch_';
        return substr( $prepend . $unique, 0, $length );
    }

    public function maybe_handle() {
        session_write_close();

        if ( $this->is_process_running() ) {
            wp_die();
        }

        check_ajax_referer( $this->identifier, 'nonce' );

        $this->handle();
        wp_die();
    }

    protected function is_queue_empty() {
        global $wpdb;
        $key   = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $key
            )
        );
        return ( $count <= 0 );
    }

    protected function is_process_running() {
        if ( get_transient( $this->identifier . '_process_lock' ) ) {
            return true;
        }
        return false;
    }

    protected function lock_process() {
        $this->start_time = time();
        $lock_duration    = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60;
        set_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
    }

    protected function unlock_process() {
        delete_transient( $this->identifier . '_process_lock' );
        return $this;
    }

    protected function get_batch() {
        global $wpdb;
        $key        = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
        $query      = $wpdb->prepare(
            "SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id ASC LIMIT 1",
            $key
        );
        $row        = $wpdb->get_row( $query );

        $batch       = new stdClass();
        $batch->key  = isset( $row->option_name ) ? $row->option_name : '';
        $batch->data = isset( $row->option_value ) ? maybe_unserialize( $row->option_value ) : array();

        return $batch;
    }

    protected function handle() {
        $this->lock_process();

        do {
            $batch = $this->get_batch();

            if ( empty( $batch->data ) ) {
                break;
            }

            foreach ( $batch->data as $key => $value ) {
                // FAST CHECK: Emergency stop transient (set by cancel()).
                if ( get_transient( 'ablr_emergency_stop' ) ) {
                    $this->delete( $batch->key );
                    $this->unlock_process();
                    $this->clear_remaining_batches();
                    $this->clear_scheduled_event();
                    delete_transient( 'ablr_emergency_stop' );
                    return;
                }

                // Check if scan was cancelled or stopped — bail out immediately.
                // Read directly from database to bypass ALL caching (object cache, Redis, Memcached).
                global $wpdb;
                wp_cache_delete( 'ablr_scan_progress', 'options' );
                $raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" );
                $progress = $raw_value ? maybe_unserialize( $raw_value ) : array();
                if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
                    // Clear remaining queue and stop processing.
                    $this->delete( $batch->key );
                    $this->unlock_process();
                    $this->clear_remaining_batches();
                    $this->clear_scheduled_event();
                    return;
                }

                $task = $this->task( $value );

                if ( false !== $task ) {
                    $batch->data[ $key ] = $task;
                } else {
                    unset( $batch->data[ $key ] );
                }

                if ( $this->time_exceeded() || $this->memory_exceeded() ) {
                    break;
                }
            }

            if ( empty( $batch->data ) ) {
                $this->delete( $batch->key );
            } else {
                $this->update( $batch->key, $batch->data );
            }
        } while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

        $this->unlock_process();

        // Check status before dispatching next batch - don't restart if stopped.
        global $wpdb;
        $raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" );
        $progress = $raw_value ? maybe_unserialize( $raw_value ) : array();
        if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
            $this->clear_remaining_batches();
            $this->clear_scheduled_event();
            return;
        }

        if ( ! $this->is_queue_empty() ) {
            $this->dispatch();
        } else {
            $this->complete();
        }
    }

    protected function update( $key, $data ) {
        if ( ! empty( $data ) ) {
            update_option( $key, $data, false );
        }
    }

    protected function delete( $key ) {
        delete_option( $key );
    }

    protected function time_exceeded() {
        $finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 30 );
        return ( time() >= $finish );
    }

    protected function memory_exceeded() {
        $memory_limit   = $this->get_memory_limit() * 0.9;
        $current_memory = memory_get_usage( true );
        return ( $current_memory >= $memory_limit );
    }

    protected function get_memory_limit() {
        if ( function_exists( 'ini_get' ) ) {
            $memory_limit = ini_get( 'memory_limit' );
        } else {
            $memory_limit = '128M';
        }

        if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
            $memory_limit = '32000M';
        }

        return wp_convert_hr_to_bytes( $memory_limit );
    }

    protected function schedule_event() {
        if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
            // wp_schedule_event can return WP_Error in WP 5.1+ if cron option is locked.
            // Suppress errors - process works via AJAX, cron is just a healthcheck fallback.
            $result = @wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
            if ( is_wp_error( $result ) ) {
                return false;
            }
        }
        return true;
    }

    protected function clear_scheduled_event() {
        // Use wp_clear_scheduled_hook to remove ALL instances of recurring event.
        // wp_unschedule_event only removes one occurrence.
        wp_clear_scheduled_hook( $this->cron_hook_identifier );
    }

    public function cancel() {
        // Set emergency stop transient FIRST - this is checked frequently and is fast.
        set_transient( 'ablr_emergency_stop', '1', 300 );

        global $wpdb;
        $key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $key
            )
        );
        $this->unlock_process();
        $this->clear_scheduled_event();
        delete_option( $this->identifier . '_cancelled' );

        // Clear object cache for progress option.
        wp_cache_delete( 'ablr_scan_progress', 'options' );
    }

    /**
     * Clear any remaining batch options from the queue.
     * Used when handle() detects a cancelled/stopped state mid-processing.
     */
    protected function clear_remaining_batches() {
        global $wpdb;
        $key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $key
            )
        );
    }

    public function handle_cron_healthcheck() {
        if ( $this->is_process_running() ) {
            return;
        }
        if ( $this->is_queue_empty() ) {
            $this->clear_scheduled_event();
            return;
        }

        // Do not resume if scan was explicitly cancelled or stopped.
        // Read directly from database to bypass ALL caching (object cache, Redis, Memcached).
        global $wpdb;
        $raw_value = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'ablr_scan_progress' LIMIT 1" );
        $progress = $raw_value ? maybe_unserialize( $raw_value ) : array();
        if ( isset( $progress['status'] ) && in_array( $progress['status'], array( 'cancelled', 'stopped' ), true ) ) {
            // Clear leftover queue and cron.
            $this->clear_remaining_batches();
            $this->clear_scheduled_event();
            return;
        }

        // Process directly in cron context (more reliable than loopback).
        $this->handle();
    }

    public function schedule_cron_healthcheck( $schedules ) {
        $interval = apply_filters( $this->identifier . '_cron_interval', 5 );
        $schedules[ $this->cron_interval_identifier ] = array(
            'interval' => MINUTE_IN_SECONDS * $interval,
            'display'  => sprintf( __( 'Every %d Minutes', 'archivarix-broken-links-recovery' ), $interval ),
        );
        return $schedules;
    }

    public function is_active() {
        return ! $this->is_queue_empty() || $this->is_process_running();
    }

    protected function complete() {
        $this->clear_scheduled_event();
        do_action( $this->identifier . '_complete' );
    }

    abstract protected function task( $item );
}
