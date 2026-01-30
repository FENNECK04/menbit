<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Scheduler {
    public const ACTION_HOOK = 'mbc_daily_renewal_scan';

    public static function init(): void {
        add_action( self::ACTION_HOOK, array( __CLASS__, 'run_renewal_scan' ) );
        add_action( 'admin_post_mbc_run_renewal', array( __CLASS__, 'run_manual_scan' ) );
    }

    public static function schedule_recurring(): void {
        if ( function_exists( 'as_next_scheduled_action' ) ) {
            if ( ! as_next_scheduled_action( self::ACTION_HOOK ) ) {
                as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::ACTION_HOOK, array(), 'mbc' );
            }
        } else {
            if ( ! wp_next_scheduled( self::ACTION_HOOK ) ) {
                wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::ACTION_HOOK );
            }
        }
    }

    public static function run_manual_scan(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mbc_run_renewal' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        self::run_renewal_scan();
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function run_renewal_scan(): void {
        global $wpdb;
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );

        $renewal_days = absint( $settings['renewal_days'] );
        $reminder_days = absint( $settings['renewal_reminder_days'] );
        $inactive_days = absint( $settings['renewal_inactive_days'] );

        $threshold_date = gmdate( 'Y-m-d H:i:s', time() - ( $renewal_days * DAY_IN_SECONDS ) );
        $candidates = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'active' AND last_cv_at IS NOT NULL AND last_cv_at < %s", $threshold_date ) );

        foreach ( $candidates as $candidate ) {
            self::process_candidate_renewal( $candidate, $reminder_days, $inactive_days );
        }
    }

    private static function process_candidate_renewal( object $candidate, int $reminder_days, int $inactive_days ): void {
        $now = current_time( 'timestamp' );
        $last_email = $candidate->last_renewal_email_at ? strtotime( $candidate->last_renewal_email_at ) : 0;

        if ( 'none' === $candidate->renewal_state ) {
            self::send_renewal_email( $candidate );
            return;
        }

        if ( 'emailed' === $candidate->renewal_state && $last_email && $now > ( $last_email + ( $reminder_days * DAY_IN_SECONDS ) ) ) {
            self::send_reminder_email( $candidate );
            return;
        }

        if ( 'reminded' === $candidate->renewal_state && $last_email && $now > ( $last_email + ( $inactive_days * DAY_IN_SECONDS ) ) ) {
            self::mark_inactive( $candidate );
        }
    }

    private static function send_renewal_email( object $candidate ): void {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $token = MBC_Tokens::generate_token( $candidate->id, $candidate->email );
        $update_link = add_query_arg( 'token', rawurlencode( $token ), home_url( '/update-cv/' ) );

        $subject = str_replace( '{first_name}', $candidate->first_name, $settings['renewal_subject'] );
        $body = str_replace( '{first_name}', $candidate->first_name, $settings['renewal_body'] );
        $body .= "\n\nUpdate your CV: {$update_link}";

        wp_mail(
            $candidate->email,
            $subject,
            $body,
            array( 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>' )
        );

        self::update_renewal_state( $candidate->id, 'emailed' );
        MBC_Candidates::log_activity( $candidate->id, 'email_sent', array( 'type' => 'renewal' ) );
    }

    private static function send_reminder_email( object $candidate ): void {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $token = MBC_Tokens::generate_token( $candidate->id, $candidate->email );
        $update_link = add_query_arg( 'token', rawurlencode( $token ), home_url( '/update-cv/' ) );

        $subject = str_replace( '{first_name}', $candidate->first_name, $settings['reminder_subject'] );
        $body = str_replace( '{first_name}', $candidate->first_name, $settings['reminder_body'] );
        $body .= "\n\nUpdate your CV: {$update_link}";

        wp_mail(
            $candidate->email,
            $subject,
            $body,
            array( 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>' )
        );

        self::update_renewal_state( $candidate->id, 'reminded' );
        MBC_Candidates::log_activity( $candidate->id, 'email_sent', array( 'type' => 'reminder' ) );
    }

    private static function mark_inactive( object $candidate ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $wpdb->update(
            $table,
            array( 'status' => 'inactive', 'renewal_state' => 'inactive_marked', 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $candidate->id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );
        MBC_Candidates::log_activity( $candidate->id, 'status_changed', array( 'status' => 'inactive' ) );
    }

    private static function update_renewal_state( int $candidate_id, string $state ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $wpdb->update(
            $table,
            array( 'renewal_state' => $state, 'last_renewal_email_at' => current_time( 'mysql' ) ),
            array( 'id' => $candidate_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public static function self_check(): string {
        global $wpdb;
        $tables = array(
            MBC_TABLE_CANDIDATES,
            MBC_TABLE_CV,
            MBC_TABLE_ACTIVITY,
            MBC_TABLE_JETS,
        );
        $results = array();
        foreach ( $tables as $table ) {
            $full = MBC_DB::table( $table );
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) );
            $results[] = $full . ': ' . ( $exists ? 'OK' : 'Missing' );
        }
        $scheduled = function_exists( 'as_next_scheduled_action' ) ? as_next_scheduled_action( self::ACTION_HOOK ) : wp_next_scheduled( self::ACTION_HOOK );
        $results[] = 'Scheduler: ' . ( $scheduled ? 'Scheduled' : 'Not scheduled' );
        return implode( "\n", $results );
    }
}
