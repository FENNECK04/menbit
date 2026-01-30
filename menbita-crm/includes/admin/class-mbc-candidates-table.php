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
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'experience' => 'Experience',
            'availability' => 'Availability',
            'owner' => 'Owner',
            'stage' => 'Stage',
            'status' => 'Status',
            'last_cv' => 'Last CV',
            'last_activity' => 'Last Activity',
        );
    }

    protected function column_name( $item ): string {
        $view_link = add_query_arg( array( 'page' => 'mbc-candidate', 'candidate_id' => $item->id ), admin_url( 'admin.php' ) );
        $actions = array(
            'view' => sprintf( '<a href="%s">View</a>', esc_url( $view_link ) ),
        );
        return sprintf( '%s %s', esc_html( $item->first_name . ' ' . $item->last_name ), $this->row_actions( $actions ) );
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
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

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
