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
        fputcsv( $output, array( 'Name', 'Email', 'Phone', 'Experience', 'Availability', 'Sectors', 'Industries', 'Stage', 'Status', 'Last CV', 'Dossier Status', 'Feedback' ) );
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

        $rows = array();
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT jc.*, c.* FROM {$jet_candidates} jc JOIN {$candidates} c ON jc.candidate_id = c.id WHERE jc.jet_id = %d", $jet_id ) );
        foreach ( $items as $item ) {
            $sectors = $wpdb->get_col( $wpdb->prepare( "SELECT sector_slug FROM {$sectors_table} WHERE candidate_id = %d", $item->candidate_id ) );
            $industries = $wpdb->get_col( $wpdb->prepare( "SELECT industry_slug FROM {$industries_table} WHERE candidate_id = %d", $item->candidate_id ) );

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
            );
        }
        return $rows;
    }
}
