<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Candidates_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'candidate',
                'plural' => 'candidates',
                'ajax' => false,
            )
        );
    }

    public function get_columns(): array {
        return array(
            'cb' => '<input type="checkbox" />',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'experience' => 'Experience',
            'availability' => 'Availability',
            'sectors' => 'Sectors',
            'industries' => 'Industries',
            'owner' => 'Owner',
            'stage' => 'Stage',
            'status' => 'Status',
            'last_cv' => 'Last CV',
            'last_activity' => 'Last Activity',
        );
    }

    protected function column_name( $item ): string {
        $view_link = add_query_arg( array( 'page' => 'mbc-candidate', 'candidate_id' => $item->id ), admin_url( 'admin.php' ) );
        $note_link = $view_link . '#mbc-notes';
        $jet_link = $view_link . '#mbc-jets';
        $actions = array(
            'view' => sprintf( '<a href="%s">View</a>', esc_url( $view_link ) ),
            'add_note' => sprintf( '<a href="%s">Add note</a>', esc_url( $note_link ) ),
            'add_to_jet' => sprintf( '<a href="%s">Add to jet</a>', esc_url( $jet_link ) ),
        );
        return sprintf( '%s %s', esc_html( $item->first_name . ' ' . $item->last_name ), $this->row_actions( $actions ) );
    }

    protected function column_cb( $item ): string {
        return sprintf( '<input type="checkbox" name="candidate_id[]" value="%d" />', $item->id );
    }

    protected function get_bulk_actions(): array {
        return array(
            'export_csv' => 'Export CSV',
        );
    }

    protected function extra_tablenav( $which ): void {
        if ( 'top' !== $which ) {
            return;
        }
        $current_status = sanitize_text_field( wp_unslash( $_GET['mbc_status'] ?? '' ) );
        $current_stage = sanitize_text_field( wp_unslash( $_GET['mbc_stage'] ?? '' ) );
        $current_owner = absint( $_GET['mbc_owner'] ?? 0 );
        $current_experience = sanitize_text_field( wp_unslash( $_GET['mbc_experience'] ?? '' ) );
        $current_availability = sanitize_text_field( wp_unslash( $_GET['mbc_availability'] ?? '' ) );
        $current_sector = sanitize_text_field( wp_unslash( $_GET['mbc_sector'] ?? '' ) );
        $current_industry = sanitize_text_field( wp_unslash( $_GET['mbc_industry'] ?? '' ) );
        $current_date_from = sanitize_text_field( wp_unslash( $_GET['mbc_date_from'] ?? '' ) );
        $current_date_to = sanitize_text_field( wp_unslash( $_GET['mbc_date_to'] ?? '' ) );
        echo '<div class="alignleft actions">';
        echo '<select name="mbc_status"><option value="">All statuses</option>';
        foreach ( MBC_Candidates::statuses_list() as $status ) {
            echo '<option value="' . esc_attr( $status ) . '" ' . selected( $current_status, $status, false ) . '>' . esc_html( $status ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_stage"><option value="">All stages</option>';
        foreach ( MBC_Candidates::pipeline_stages() as $stage ) {
            echo '<option value="' . esc_attr( $stage ) . '" ' . selected( $current_stage, $stage, false ) . '>' . esc_html( $stage ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_experience"><option value="">All experience</option>';
        foreach ( array( 'student', '0_3', '3_8', '8_plus' ) as $experience ) {
            echo '<option value="' . esc_attr( $experience ) . '" ' . selected( $current_experience, $experience, false ) . '>' . esc_html( $experience ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_availability"><option value="">All availability</option>';
        foreach ( array( 'asap', 'notice', 'other' ) as $availability ) {
            echo '<option value="' . esc_attr( $availability ) . '" ' . selected( $current_availability, $availability, false ) . '>' . esc_html( $availability ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_sector"><option value="">All sectors</option>';
        foreach ( MBC_Candidates::sectors_list() as $slug => $label ) {
            echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $current_sector, $slug, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_industry"><option value="">All industries</option>';
        foreach ( MBC_Candidates::industries_list() as $slug => $label ) {
            echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $current_industry, $slug, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        wp_dropdown_users(
            array(
                'name' => 'mbc_owner',
                'selected' => $current_owner,
                'show_option_none' => 'All owners',
                'echo' => true,
            )
        );
        echo '<input type="date" name="mbc_date_from" value="' . esc_attr( $current_date_from ) . '" placeholder="From">';
        echo '<input type="date" name="mbc_date_to" value="' . esc_attr( $current_date_to ) . '" placeholder="To">';
        submit_button( 'Filter', 'secondary', 'filter_action', false );
        echo '</div>';

        echo '<div class="alignleft actions">';
        echo '<select name="mbc_bulk_status"><option value="">Bulk status...</option>';
        foreach ( MBC_Candidates::statuses_list() as $status ) {
            echo '<option value="' . esc_attr( $status ) . '">' . esc_html( $status ) . '</option>';
        }
        echo '</select>';
        echo '<select name="mbc_bulk_stage"><option value="">Bulk stage...</option>';
        foreach ( MBC_Candidates::pipeline_stages() as $stage ) {
            echo '<option value="' . esc_attr( $stage ) . '">' . esc_html( $stage ) . '</option>';
        }
        echo '</select>';
        wp_dropdown_users(
            array(
                'name' => 'mbc_bulk_owner',
                'show_option_none' => 'Bulk owner...',
                'echo' => true,
            )
        );
        submit_button( 'Apply Bulk', 'secondary', 'mbc_bulk_apply', false );
        echo '</div>';
    }

    public function process_bulk_action(): void {
        if ( ! $this->current_action() && ! isset( $_REQUEST['mbc_bulk_apply'] ) ) {
            return;
        }
        check_admin_referer( 'bulk-' . $this->_args['plural'] );
        $ids = array_map( 'absint', $_REQUEST['candidate_id'] ?? array() );
        if ( empty( $ids ) ) {
            return;
        }
        if ( 'export_csv' === $this->current_action() ) {
            MBC_Exports::export_candidates_csv( $ids );
        }

        if ( isset( $_REQUEST['mbc_bulk_apply'] ) ) {
            $bulk_status = sanitize_text_field( wp_unslash( $_REQUEST['mbc_bulk_status'] ?? '' ) );
            $bulk_stage = sanitize_text_field( wp_unslash( $_REQUEST['mbc_bulk_stage'] ?? '' ) );
            $bulk_owner = absint( $_REQUEST['mbc_bulk_owner'] ?? 0 );
            global $wpdb;
            $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
            foreach ( $ids as $candidate_id ) {
                $data = array( 'updated_at' => current_time( 'mysql' ) );
                $formats = array( '%s' );
                if ( $bulk_status ) {
                    $data['status'] = $bulk_status;
                    $formats[] = '%s';
                }
                if ( $bulk_stage ) {
                    $data['pipeline_stage'] = $bulk_stage;
                    $formats[] = '%s';
                }
                if ( $bulk_owner ) {
                    $data['owner_user_id'] = $bulk_owner;
                    $formats[] = '%d';
                }
                if ( count( $data ) > 1 ) {
                    $wpdb->update(
                        $table,
                        $data,
                        array( 'id' => $candidate_id ),
                        $formats,
                        array( '%d' )
                    );
                }
            }
        }
    }

    protected function column_default( $item, $column_name ): string {
        switch ( $column_name ) {
            case 'email':
                return esc_html( $item->email );
            case 'phone':
                return esc_html( $item->phone );
            case 'experience':
                return esc_html( $item->experience_bracket );
            case 'availability':
                return esc_html( $item->availability_type );
            case 'sectors':
                return esc_html( $item->sectors ?? '' );
            case 'industries':
                return esc_html( $item->industries ?? '' );
            case 'owner':
                return $item->owner_user_id ? esc_html( get_the_author_meta( 'display_name', $item->owner_user_id ) ) : '—';
            case 'stage':
                return esc_html( $item->pipeline_stage );
            case 'status':
                return esc_html( $item->status );
            case 'last_cv':
                return esc_html( $item->last_cv_at );
            case 'last_activity':
                return esc_html( $item->last_activity_at );
            default:
                return '';
        }
    }

    public function prepare_items(): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $sectors_table = MBC_DB::table( MBC_TABLE_SECTORS );
        $industries_table = MBC_DB::table( MBC_TABLE_INDUSTRIES );
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        $where = array();
        $params = array();

        $status = sanitize_text_field( wp_unslash( $_GET['mbc_status'] ?? '' ) );
        if ( $status ) {
            $where[] = 'c.status = %s';
            $params[] = $status;
        }
        $stage = sanitize_text_field( wp_unslash( $_GET['mbc_stage'] ?? '' ) );
        if ( $stage ) {
            $where[] = 'c.pipeline_stage = %s';
            $params[] = $stage;
        }
        $owner = absint( $_GET['mbc_owner'] ?? 0 );
        if ( $owner ) {
            $where[] = 'c.owner_user_id = %d';
            $params[] = $owner;
        }
        $experience = sanitize_text_field( wp_unslash( $_GET['mbc_experience'] ?? '' ) );
        if ( $experience ) {
            $where[] = 'c.experience_bracket = %s';
            $params[] = $experience;
        }
        $availability = sanitize_text_field( wp_unslash( $_GET['mbc_availability'] ?? '' ) );
        if ( $availability ) {
            $where[] = 'c.availability_type = %s';
            $params[] = $availability;
        }
        $sector = sanitize_text_field( wp_unslash( $_GET['mbc_sector'] ?? '' ) );
        if ( $sector ) {
            $where[] = 's.sector_slug = %s';
            $params[] = $sector;
        }
        $industry = sanitize_text_field( wp_unslash( $_GET['mbc_industry'] ?? '' ) );
        if ( $industry ) {
            $where[] = 'i.industry_slug = %s';
            $params[] = $industry;
        }
        $date_from = sanitize_text_field( wp_unslash( $_GET['mbc_date_from'] ?? '' ) );
        if ( $date_from ) {
            $where[] = 'c.created_at >= %s';
            $params[] = $date_from . ' 00:00:00';
        }
        $date_to = sanitize_text_field( wp_unslash( $_GET['mbc_date_to'] ?? '' ) );
        if ( $date_to ) {
            $where[] = 'c.created_at <= %s';
            $params[] = $date_to . ' 23:59:59';
        }
        $search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
        if ( $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where[] = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, GROUP_CONCAT(DISTINCT s.sector_slug SEPARATOR ', ') AS sectors, GROUP_CONCAT(DISTINCT i.industry_slug SEPARATOR ', ') AS industries
                FROM {$table} c
                LEFT JOIN {$sectors_table} s ON c.id = s.candidate_id
                LEFT JOIN {$industries_table} i ON c.id = i.candidate_id
                {$where_sql}
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT %d OFFSET %d",
                array_merge( $params, array( $per_page, $offset ) )
            )
        );
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} c {$where_sql}", $params ) );

        $this->items = $items;
        $this->set_pagination_args(
            array(
                'total_items' => $total,
                'per_page' => $per_page,
                'total_pages' => ceil( $total / $per_page ),
            )
        );
    }
}
