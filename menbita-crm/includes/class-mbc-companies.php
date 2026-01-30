<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Companies {
    public static function init(): void {
        add_action( 'admin_post_mbc_create_company', array( __CLASS__, 'handle_create_company' ) );
    }

    public static function handle_create_company(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_create_company' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( empty( $name ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_COMPANIES );
        $wpdb->insert(
            $table,
            array(
                'name' => $name,
                'sector' => sanitize_text_field( wp_unslash( $_POST['sector'] ?? '' ) ),
                'notes' => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function get_companies(): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_COMPANIES );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
    }
}
