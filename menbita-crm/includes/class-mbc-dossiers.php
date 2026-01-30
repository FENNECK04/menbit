<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Dossiers {
    public static function init(): void {
        add_action( 'admin_post_mbc_create_dossier', array( __CLASS__, 'handle_create_dossier' ) );
        add_action( 'admin_post_mbc_create_jet', array( __CLASS__, 'handle_create_jet' ) );
        add_action( 'admin_post_mbc_add_to_jet', array( __CLASS__, 'handle_add_to_jet' ) );
    }

    public static function handle_create_dossier(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_create_dossier' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
        if ( empty( $title ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_DOSSIERS );
        $wpdb->insert(
            $table,
            array(
                'company_id' => null,
                'event_id' => null,
                'title' => $title,
                'description' => $description,
                'created_by_user_id' => get_current_user_id(),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
        );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_create_jet(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['dossier_id'] ) || empty( $_POST['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_create_jet' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $dossier_id = absint( $_POST['dossier_id'] );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( empty( $dossier_id ) || empty( $name ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_JETS );
        $wpdb->insert(
            $table,
            array(
                'dossier_id' => $dossier_id,
                'name' => $name,
                'order_index' => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%d', '%s' )
        );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function handle_add_to_jet(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['candidate_id'] ) || empty( $_POST['jet_id'] ) || empty( $_POST['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_add_to_jet' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $candidate_id = absint( $_POST['candidate_id'] );
        $jet_id = absint( $_POST['jet_id'] );
        $dossier_status = sanitize_text_field( wp_unslash( $_POST['dossier_status'] ?? 'proposed' ) );
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_JET_CANDIDATES );
        $wpdb->replace(
            $table,
            array(
                'jet_id' => $jet_id,
                'candidate_id' => $candidate_id,
                'dossier_status' => $dossier_status,
                'feedback' => '',
                'added_by_user_id' => get_current_user_id(),
                'added_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%s' )
        );
        MBC_Candidates::log_activity( $candidate_id, 'added_to_jet', array( 'jet_id' => $jet_id ), get_current_user_id() );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function get_dossiers(): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_DOSSIERS );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
    }

    public static function get_jets_by_dossier( int $dossier_id ): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_JETS );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE dossier_id = %d ORDER BY order_index ASC", $dossier_id ) );
    }

    public static function get_all_jets(): array {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_JETS );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
    }
}
