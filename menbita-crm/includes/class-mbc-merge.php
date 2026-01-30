<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Merge {
    public static function init(): void {
        add_action( 'admin_post_mbc_merge_candidates', array( __CLASS__, 'handle_merge' ) );
    }

    public static function handle_merge(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_POST['primary_id'] ) || empty( $_POST['secondary_id'] ) || empty( $_POST['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mbc_merge_candidates' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $primary_id = absint( $_POST['primary_id'] );
        $secondary_id = absint( $_POST['secondary_id'] );
        if ( $primary_id === $secondary_id ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        self::merge_candidates( $primary_id, $secondary_id );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    public static function merge_candidates( int $primary_id, int $secondary_id ): void {
        global $wpdb;
        $tables_to_update = array(
            MBC_TABLE_CV => 'candidate_id',
            MBC_TABLE_NOTES => 'candidate_id',
            MBC_TABLE_ACTIVITY => 'candidate_id',
            MBC_TABLE_EVENT_ATTENDANCE => 'candidate_id',
            MBC_TABLE_JET_CANDIDATES => 'candidate_id',
        );

        foreach ( $tables_to_update as $table => $column ) {
            $wpdb->update(
                MBC_DB::table( $table ),
                array( $column => $primary_id ),
                array( $column => $secondary_id ),
                array( '%d' ),
                array( '%d' )
            );
        }

        self::merge_terms( $primary_id, $secondary_id, MBC_TABLE_SECTORS, 'sector_slug' );
        self::merge_terms( $primary_id, $secondary_id, MBC_TABLE_INDUSTRIES, 'industry_slug' );

        $wpdb->update(
            MBC_DB::table( MBC_TABLE_CANDIDATES ),
            array( 'status' => 'obsolete', 'duplicate_status' => 'merged', 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $secondary_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        MBC_Candidates::log_activity( $primary_id, 'merged', array( 'secondary_id' => $secondary_id ) );
    }

    private static function merge_terms( int $primary_id, int $secondary_id, string $table_name, string $column ): void {
        global $wpdb;
        $table = MBC_DB::table( $table_name );
        $terms = $wpdb->get_col( $wpdb->prepare( \"SELECT {$column} FROM {$table} WHERE candidate_id = %d\", $secondary_id ) );
        foreach ( $terms as $term ) {
            $wpdb->query(
                $wpdb->prepare(
                    \"INSERT IGNORE INTO {$table} (candidate_id, {$column}) VALUES (%d, %s)\",
                    $primary_id,
                    $term
                )
            );
        }
        $wpdb->delete( $table, array( 'candidate_id' => $secondary_id ), array( '%d' ) );
    }
}
