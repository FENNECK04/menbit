<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Events {
    public static function init(): void {
        add_action( 'admin_post_mbc_create_event', array( __CLASS__, 'handle_create_event' ) );
        add_action( 'admin_post_mbc_add_event_attendance', array( __CLASS__, 'handle_add_attendance' ) );
    }

    public static function handle_create_event(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_create_event' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( empty( $name ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_EVENTS );
        $wpdb->insert(
            $table,
            array(
                'name' => $name,
                'location' => sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ),
                'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
                'end_date' => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
                'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_add_attendance(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['candidate_id'] ) || empty( $_POST['event_id'] ) || empty( $_POST['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_add_event_attendance' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_EVENT_ATTENDANCE );
        $wpdb->replace(
            $table,
            array(
                'event_id' => absint( $_POST['event_id'] ),
                'candidate_id' => absint( $_POST['candidate_id'] ),
                'status' => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'invited' ) ),
                'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
            ),
            array( '%d', '%d', '%s', '%s' )
        );
        MBC_Candidates::log_activity(
            absint( $_POST['candidate_id'] ),
            'event_linked',
            array( 'event_id' => absint( $_POST['event_id'] ) ),
            get_current_user_id()
        );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function get_events(): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_EVENTS );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY start_date DESC" );
    }

    public static function get_attendance_for_candidate( int $candidate_id ): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_EVENT_ATTENDANCE );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE candidate_id = %d", $candidate_id ) );
    }
}
