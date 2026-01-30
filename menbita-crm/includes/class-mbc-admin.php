<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Admin {
    public static function init(): void {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        }
    }

    public static function register_menu(): void {
        add_menu_page(
            'Menbita CRM',
            'Menbita CRM',
            MBC_CAPABILITY,
            'mbc-candidates',
            array( __CLASS__, 'render_candidates_page' ),
            'dashicons-id-alt',
            25
        );

        add_submenu_page(
            'mbc-candidates',
            'Candidates',
            'Candidates',
            MBC_CAPABILITY,
            'mbc-candidates',
            array( __CLASS__, 'render_candidates_page' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Candidate Detail',
            'Candidate Detail',
            MBC_CAPABILITY,
            'mbc-candidate',
            array( __CLASS__, 'render_candidate_detail' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Dossiers',
            'Dossiers',
            MBC_CAPABILITY,
            'mbc-dossiers',
            array( __CLASS__, 'render_dossiers_page' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Events',
            'Events',
            MBC_CAPABILITY,
            'mbc-events',
            array( __CLASS__, 'render_events_page' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Companies',
            'Companies',
            MBC_CAPABILITY,
            'mbc-companies',
            array( __CLASS__, 'render_companies_page' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Duplicates',
            'Duplicates',
            MBC_CAPABILITY,
            'mbc-duplicates',
            array( __CLASS__, 'render_duplicates_page' )
        );

        add_submenu_page(
            'mbc-candidates',
            'Settings',
            'Settings',
            MBC_CAPABILITY,
            'mbc-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function register_settings(): void {
        register_setting( 'mbc_settings_group', 'mbc_settings', array( __CLASS__, 'sanitize_settings' ) );

        add_settings_section( 'mbc_email_section', 'Email Templates', '__return_false', 'mbc-settings' );
        add_settings_field( 'from_name', 'From Name', array( __CLASS__, 'render_text_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'from_name' ) );
        add_settings_field( 'from_email', 'From Email', array( __CLASS__, 'render_text_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'from_email' ) );
        add_settings_field( 'confirmation_subject', 'Confirmation Subject', array( __CLASS__, 'render_text_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'confirmation_subject' ) );
        add_settings_field( 'confirmation_body', 'Confirmation Body', array( __CLASS__, 'render_textarea_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'confirmation_body' ) );
        add_settings_field( 'renewal_subject', 'Renewal Subject', array( __CLASS__, 'render_text_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'renewal_subject' ) );
        add_settings_field( 'renewal_body', 'Renewal Body', array( __CLASS__, 'render_textarea_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'renewal_body' ) );
        add_settings_field( 'reminder_subject', 'Reminder Subject', array( __CLASS__, 'render_text_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'reminder_subject' ) );
        add_settings_field( 'reminder_body', 'Reminder Body', array( __CLASS__, 'render_textarea_field' ), 'mbc-settings', 'mbc_email_section', array( 'key' => 'reminder_body' ) );

        add_settings_section( 'mbc_renewal_section', 'Renewal Rules', '__return_false', 'mbc-settings' );
        add_settings_field( 'token_expiration_days', 'Token Expiration Days', array( __CLASS__, 'render_number_field' ), 'mbc-settings', 'mbc_renewal_section', array( 'key' => 'token_expiration_days' ) );
        add_settings_field( 'renewal_days', 'Renewal Threshold Days', array( __CLASS__, 'render_number_field' ), 'mbc-settings', 'mbc_renewal_section', array( 'key' => 'renewal_days' ) );
        add_settings_field( 'renewal_reminder_days', 'Reminder Days', array( __CLASS__, 'render_number_field' ), 'mbc-settings', 'mbc_renewal_section', array( 'key' => 'renewal_reminder_days' ) );
        add_settings_field( 'renewal_inactive_days', 'Inactive Days', array( __CLASS__, 'render_number_field' ), 'mbc-settings', 'mbc_renewal_section', array( 'key' => 'renewal_inactive_days' ) );

        add_settings_section( 'mbc_upload_section', 'Uploads', '__return_false', 'mbc-settings' );
        add_settings_field( 'max_upload_mb', 'Max Upload (MB)', array( __CLASS__, 'render_number_field' ), 'mbc-settings', 'mbc_upload_section', array( 'key' => 'max_upload_mb' ) );
        add_settings_field( 'gdpr_consent_text', 'Consent Text', array( __CLASS__, 'render_textarea_field' ), 'mbc-settings', 'mbc_upload_section', array( 'key' => 'gdpr_consent_text' ) );
    }

    public static function sanitize_settings( array $input ): array {
        $defaults = MBC_Security::default_settings();
        $output = array();
        foreach ( $defaults as $key => $value ) {
            if ( isset( $input[ $key ] ) ) {
                $output[ $key ] = is_string( $value ) ? sanitize_text_field( $input[ $key ] ) : absint( $input[ $key ] );
            } else {
                $output[ $key ] = $value;
            }
        }
        return $output;
    }

    public static function render_text_field( array $args ): void {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $key = $args['key'];
        printf( '<input type="text" name="mbc_settings[%s]" value="%s" class="regular-text">', esc_attr( $key ), esc_attr( $settings[ $key ] ) );
    }

    public static function render_textarea_field( array $args ): void {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $key = $args['key'];
        printf( '<textarea name="mbc_settings[%s]" rows="5" cols="50">%s</textarea>', esc_attr( $key ), esc_textarea( $settings[ $key ] ) );
    }

    public static function render_number_field( array $args ): void {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $key = $args['key'];
        printf( '<input type="number" name="mbc_settings[%s]" value="%s" class="small-text">', esc_attr( $key ), esc_attr( $settings[ $key ] ) );
    }

    public static function enqueue_assets(): void {
        wp_enqueue_style( 'mbc-admin', MBC_PLUGIN_URL . 'assets/admin.css', array(), MBC_VERSION );
    }

    public static function render_candidates_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        require_once MBC_PLUGIN_PATH . 'includes/admin/class-mbc-candidates-table.php';
        $table = new MBC_Candidates_Table();
        $table->prepare_items();
        echo '<div class="wrap"><h1>Candidates</h1>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="mbc-candidates">';
        $table->display();
        echo '</form></div>';
    }

    public static function render_candidate_detail(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        $candidate_id = absint( $_GET['candidate_id'] ?? 0 );
        $candidate = MBC_Candidates::get_candidate( $candidate_id );
        if ( ! $candidate ) {
            echo '<div class="wrap"><h1>Candidate not found</h1></div>';
            return;
        }
        global $wpdb;
        $cv_table = MBC_DB::table( MBC_TABLE_CV );
        $cv_versions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$cv_table} WHERE candidate_id = %d ORDER BY uploaded_at DESC", $candidate_id ) );

        echo '<div class="wrap">';
        echo '<h1>Candidate Detail</h1>';
        echo '<h2>' . esc_html( $candidate->first_name . ' ' . $candidate->last_name ) . '</h2>';
        echo '<p>Email: ' . esc_html( $candidate->email ) . '</p>';
        echo '<p>Phone: ' . esc_html( $candidate->phone ) . '</p>';
        echo '<h3>CV Versions</h3>';
        if ( $cv_versions ) {
            echo '<ul>';
            foreach ( $cv_versions as $cv ) {
                $download_url = wp_nonce_url( add_query_arg( array( 'action' => 'mbc_download_cv', 'cv_id' => $cv->id ), admin_url( 'admin-post.php' ) ), 'mbc_download_cv' );
                echo '<li><a href="' . esc_url( $download_url ) . '">' . esc_html( $cv->original_filename ) . '</a> (' . esc_html( $cv->uploaded_at ) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No CV uploads.</p>';
        }
        echo '</div>';
    }

    public static function render_dossiers_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        echo '<div class="wrap"><h1>Dossiers</h1><p>Manage dossiers and jets.</p></div>';
    }

    public static function render_events_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        echo '<div class="wrap"><h1>Events</h1><p>Manage events and attendance.</p></div>';
    }

    public static function render_companies_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        echo '<div class="wrap"><h1>Companies</h1><p>Manage internal companies.</p></div>';
    }

    public static function render_duplicates_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        global $wpdb;
        $activity_table = MBC_DB::table( MBC_TABLE_ACTIVITY );
        $suspects = $wpdb->get_results( "SELECT * FROM {$activity_table} WHERE action_type = 'duplicate_suspect' ORDER BY created_at DESC LIMIT 50" );
        echo '<div class="wrap"><h1>Duplicates / Merge Center</h1>';
        if ( empty( $suspects ) ) {
            echo '<p>No duplicates detected.</p>';
        } else {
            echo '<table class="widefat"><thead><tr><th>Candidate ID</th><th>Confidence</th><th>Meta</th><th>Detected</th></tr></thead><tbody>';
            foreach ( $suspects as $suspect ) {
                $meta = json_decode( $suspect->meta, true );
                echo '<tr><td>' . esc_html( $suspect->candidate_id ) . '</td><td>' . esc_html( $meta['confidence'] ?? 'unknown' ) . '</td><td><pre>' . esc_html( print_r( $meta, true ) ) . '</pre></td><td>' . esc_html( $suspect->created_at ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public static function render_settings_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        $self_check = MBC_Scheduler::self_check();
        echo '<div class="wrap"><h1>Menbita CRM Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'mbc_settings_group' );
        do_settings_sections( 'mbc-settings' );
        submit_button();
        echo '</form>';
        $run_url = wp_nonce_url( admin_url( 'admin-post.php?action=mbc_run_renewal' ), 'mbc_run_renewal' );
        echo '<p><a class="button" href="' . esc_url( $run_url ) . '">Run renewal scan now</a></p>';
        echo '<h2>System Check</h2>';
        echo '<pre>' . esc_html( $self_check ) . '</pre>';
        echo '</div>';
    }
}
