<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Exports {
    public static function init(): void {
        add_action( 'admin_post_mbc_export_jet_csv', array( __CLASS__, 'export_jet_csv' ) );
    }

    public static function export_jet_csv(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_GET['jet_id'] ) || empty( $_GET['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mbc_export_jet' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $jet_id = absint( $_GET['jet_id'] );
        $rows = self::get_jet_export_rows( $jet_id );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="jet-' . $jet_id . '-candidates.csv"' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Name', 'Email', 'Phone', 'Experience', 'Availability', 'Sectors', 'Industries', 'Stage', 'Status', 'Last CV', 'Dossier Status', 'Feedback', 'Notes' ) );
        foreach ( $rows as $row ) {
            fputcsv( $output, $row );
        }
        fclose( $output );
        exit;
    }

    private static function get_jet_export_rows( int $jet_id ): array {
        global $wpdb;
        $jet_candidates = MBC_DB::table( MBC_TABLE_JET_CANDIDATES );
        $candidates = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $sectors_table = MBC_DB::table( MBC_TABLE_SECTORS );
        $industries_table = MBC_DB::table( MBC_TABLE_INDUSTRIES );
        $notes_table = MBC_DB::table( MBC_TABLE_NOTES );

        $rows = array();
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT jc.*, c.* FROM {$jet_candidates} jc JOIN {$candidates} c ON jc.candidate_id = c.id WHERE jc.jet_id = %d", $jet_id ) );
        foreach ( $items as $item ) {
            $sectors = $wpdb->get_col( $wpdb->prepare( "SELECT sector_slug FROM {$sectors_table} WHERE candidate_id = %d", $item->candidate_id ) );
            $industries = $wpdb->get_col( $wpdb->prepare( "SELECT industry_slug FROM {$industries_table} WHERE candidate_id = %d", $item->candidate_id ) );
            $latest_note = $wpdb->get_var( $wpdb->prepare( "SELECT note FROM {$notes_table} WHERE candidate_id = %d ORDER BY created_at DESC LIMIT 1", $item->candidate_id ) );

            $rows[] = array(
                trim( $item->first_name . ' ' . $item->last_name ),
                $item->email,
                $item->phone,
                $item->experience_bracket,
                $item->availability_type,
                implode( ', ', $sectors ),
                implode( ', ', $industries ),
                $item->pipeline_stage,
                $item->status,
                $item->last_cv_at,
                $item->dossier_status,
                $item->feedback,
                $latest_note,
            );
        }
        return $rows;
    }

    public static function export_candidates_csv( array $candidate_ids ): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $candidate_ids ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        global $wpdb;
        $candidate_ids = array_map( 'absint', $candidate_ids );
        $ids_sql = implode( ',', array_filter( $candidate_ids ) );
        if ( empty( $ids_sql ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        $candidates = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $sectors_table = MBC_DB::table( MBC_TABLE_SECTORS );
        $industries_table = MBC_DB::table( MBC_TABLE_INDUSTRIES );

        $rows = $wpdb->get_results( "SELECT * FROM {$candidates} WHERE id IN ({$ids_sql})" );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="candidates-export.csv"' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Name', 'Email', 'Phone', 'Experience', 'Availability', 'Sectors', 'Industries', 'Stage', 'Status', 'Last CV', 'Last Activity' ) );
        foreach ( $rows as $row ) {
            $sectors = $wpdb->get_col( $wpdb->prepare( "SELECT sector_slug FROM {$sectors_table} WHERE candidate_id = %d", $row->id ) );
            $industries = $wpdb->get_col( $wpdb->prepare( "SELECT industry_slug FROM {$industries_table} WHERE candidate_id = %d", $row->id ) );
            fputcsv(
                $output,
                array(
                    trim( $row->first_name . ' ' . $row->last_name ),
                    $row->email,
                    $row->phone,
                    $row->experience_bracket,
                    $row->availability_type,
                    implode( ', ', $sectors ),
                    implode( ', ', $industries ),
                    $row->pipeline_stage,
                    $row->status,
                    $row->last_cv_at,
                    $row->last_activity_at,
                )
            );
        }
        fclose( $output );
        exit;
    }
}
