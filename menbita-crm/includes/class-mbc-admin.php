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

        add_settings_section( 'mbc_pipeline_section', 'Pipeline Stages', '__return_false', 'mbc-settings' );
        add_settings_field( 'pipeline_stages', 'Stages (one per line)', array( __CLASS__, 'render_textarea_field' ), 'mbc-settings', 'mbc_pipeline_section', array( 'key' => 'pipeline_stages' ) );

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
                if ( 'pipeline_stages' === $key ) {
                    $output[ $key ] = sanitize_textarea_field( $input[ $key ] );
                } elseif ( is_string( $value ) ) {
                    $output[ $key ] = sanitize_text_field( $input[ $key ] );
                } else {
                    $output[ $key ] = absint( $input[ $key ] );
                }
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
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        require_once MBC_PLUGIN_PATH . 'includes/admin/class-mbc-candidates-table.php';
        $table = new MBC_Candidates_Table();
        $table->process_bulk_action();
        $table->prepare_items();
        echo '<div class="wrap"><h1>Candidates</h1>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="mbc-candidates">';
        $table->search_box( 'Search Candidates', 'mbc-candidates-search' );
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
        $notes = MBC_Candidates::get_candidate_notes( $candidate_id );
        $activity = MBC_Candidates::get_candidate_activity( $candidate_id );
        $attendance = MBC_Events::get_attendance_for_candidate( $candidate_id );

        echo '<div class="wrap">';
        echo '<h1>Candidate Detail</h1>';
        echo '<h2>' . esc_html( $candidate->first_name . ' ' . $candidate->last_name ) . '</h2>';
        echo '<p>Email: ' . esc_html( $candidate->email ) . '</p>';
        echo '<p>Phone: ' . esc_html( $candidate->phone ) . '</p>';
        $sectors = MBC_Candidates::get_candidate_terms( $candidate_id, MBC_TABLE_SECTORS, 'sector_slug' );
        $industries = MBC_Candidates::get_candidate_terms( $candidate_id, MBC_TABLE_INDUSTRIES, 'industry_slug' );
        echo '<p>Sectors: ' . esc_html( implode( ', ', $sectors ) ) . '</p>';
        echo '<p>Industries: ' . esc_html( implode( ', ', $industries ) ) . '</p>';
        echo '<h3>Profile</h3>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_update_candidate">';
        echo '<input type="hidden" name="candidate_id" value="' . esc_attr( $candidate_id ) . '">';
        wp_nonce_field( 'mbc_update_candidate' );
        echo '<p><label>Owner</label><br>';
        wp_dropdown_users(
            array(
                'name' => 'owner_user_id',
                'selected' => $candidate->owner_user_id,
                'show_option_none' => '—',
            )
        );
        echo '</p>';
        echo '<p><label>Pipeline Stage</label><br><select name="pipeline_stage">';
        foreach ( MBC_Candidates::pipeline_stages() as $stage ) {
            echo '<option value="' . esc_attr( $stage ) . '" ' . selected( $candidate->pipeline_stage, $stage, false ) . '>' . esc_html( $stage ) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label>Status</label><br><select name="status">';
        foreach ( MBC_Candidates::statuses_list() as $status ) {
            echo '<option value="' . esc_attr( $status ) . '" ' . selected( $candidate->status, $status, false ) . '>' . esc_html( $status ) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label>Experience</label><br><select name="experience_bracket">';
        foreach ( array( 'student', '0_3', '3_8', '8_plus' ) as $experience ) {
            echo '<option value="' . esc_attr( $experience ) . '" ' . selected( $candidate->experience_bracket, $experience, false ) . '>' . esc_html( $experience ) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label>Availability</label><br><select name="availability_type">';
        foreach ( array( 'asap', 'notice', 'other' ) as $availability ) {
            echo '<option value="' . esc_attr( $availability ) . '" ' . selected( $candidate->availability_type, $availability, false ) . '>' . esc_html( $availability ) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label>Availability Note</label><br><input type="text" name="availability_note" value="' . esc_attr( $candidate->availability_note ) . '" class="regular-text"></p>';
        submit_button( 'Update Candidate', 'primary', 'submit', false );
        echo '</form>';
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
        echo '<h3>Upload New CV (Staff)</h3>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="mbc_upload_cv">';
        echo '<input type="hidden" name="candidate_id" value="' . esc_attr( $candidate_id ) . '">';
        wp_nonce_field( 'mbc_upload_cv' );
        echo '<input type="file" name="mbc_cv" required> ';
        submit_button( 'Upload CV', 'secondary', 'submit', false );
        echo '</form>';

        $renewal_url = wp_nonce_url( admin_url( 'admin-post.php?action=mbc_send_renewal&candidate_id=' . $candidate_id ), 'mbc_send_renewal' );
        echo '<p><a class="button" href="' . esc_url( $renewal_url ) . '">Send renewal email now</a></p>';

        $jets = MBC_Dossiers::get_all_jets();
        echo '<h3 id="mbc-jets">Add to Jet</h3>';
        if ( $jets ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="mbc_add_to_jet">';
            echo '<input type="hidden" name="candidate_id" value="' . esc_attr( $candidate_id ) . '">';
            wp_nonce_field( 'mbc_add_to_jet' );
            echo '<select name="jet_id">';
            foreach ( $jets as $jet ) {
                echo '<option value="' . esc_attr( $jet->id ) . '">' . esc_html( $jet->name ) . '</option>';
            }
            echo '</select> ';
            echo '<select name="dossier_status">';
            foreach ( array( 'proposed', 'viewed', 'shortlisted', 'rejected', 'interview_scheduled', 'hired' ) as $status ) {
                echo '<option value="' . esc_attr( $status ) . '">' . esc_html( $status ) . '</option>';
            }
            echo '</select> ';
            submit_button( 'Add to Jet', 'secondary', 'submit', false );
            echo '</form>';
        } else {
            echo '<p>No jets available.</p>';
        }

        echo '<h3 id="mbc-notes">Notes</h3>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_add_note">';
        echo '<input type="hidden" name="candidate_id" value="' . esc_attr( $candidate_id ) . '">';
        wp_nonce_field( 'mbc_add_note' );
        echo '<textarea name="note" rows="4" cols="60"></textarea><br>';
        submit_button( 'Add Note', 'primary', 'submit', false );
        echo '</form>';
        if ( $notes ) {
            echo '<ul>';
            foreach ( $notes as $note ) {
                $author = get_the_author_meta( 'display_name', $note->author_user_id );
                echo '<li><strong>' . esc_html( $author ) . ':</strong> ' . esc_html( $note->note ) . ' <em>(' . esc_html( $note->created_at ) . ')</em></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No notes yet.</p>';
        }

        echo '<h3>Activity</h3>';
        if ( $activity ) {
            echo '<ul>';
            foreach ( $activity as $event ) {
                echo '<li>' . esc_html( $event->created_at . ' - ' . $event->action_type ) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No activity yet.</p>';
        }

        $events = MBC_Events::get_events();
        echo '<h3>Event Attendance</h3>';
        if ( $events ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="mbc_add_event_attendance">';
            echo '<input type="hidden" name="candidate_id" value="' . esc_attr( $candidate_id ) . '">';
            wp_nonce_field( 'mbc_add_event_attendance' );
            echo '<select name="event_id">';
            foreach ( $events as $event ) {
                echo '<option value="' . esc_attr( $event->id ) . '">' . esc_html( $event->name ) . '</option>';
            }
            echo '</select> ';
            echo '<select name="status">';
            foreach ( array( 'invited', 'registered', 'attended', 'no_show' ) as $status ) {
                echo '<option value="' . esc_attr( $status ) . '">' . esc_html( $status ) . '</option>';
            }
            echo '</select> ';
            echo '<input type="text" name="notes" placeholder="Notes"> ';
            submit_button( 'Link Event', 'secondary', 'submit', false );
            echo '</form>';
        }
        if ( $attendance ) {
            $event_lookup = array();
            foreach ( $events as $event ) {
                $event_lookup[ $event->id ] = $event->name;
            }
            echo '<ul>';
            foreach ( $attendance as $record ) {
                $event_name = $event_lookup[ $record->event_id ] ?? ( 'Event #' . $record->event_id );
                echo '<li>' . esc_html( $event_name ) . ' - ' . esc_html( $record->status ) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No event attendance yet.</p>';
        }
        echo '</div>';
    }

    public static function render_dossiers_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        $dossiers = MBC_Dossiers::get_dossiers();
        echo '<div class="wrap"><h1>Dossiers</h1>';
        echo '<h2>Create Dossier</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_create_dossier">';
        wp_nonce_field( 'mbc_create_dossier' );
        echo '<p><input type="text" name="title" placeholder="Dossier title" class="regular-text" required></p>';
        echo '<p><textarea name="description" rows="3" cols="60" placeholder="Description"></textarea></p>';
        submit_button( 'Create Dossier', 'primary', 'submit', false );
        echo '</form>';

        if ( $dossiers ) {
            foreach ( $dossiers as $dossier ) {
                echo '<hr><h2>' . esc_html( $dossier->title ) . '</h2>';
                echo '<p>' . esc_html( $dossier->description ) . '</p>';
                $jets = MBC_Dossiers::get_jets_by_dossier( (int) $dossier->id );
                echo '<h3>Jets</h3>';
                if ( $jets ) {
                    echo '<ul>';
                    foreach ( $jets as $jet ) {
                        $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=mbc_export_jet_csv&jet_id=' . $jet->id ), 'mbc_export_jet' );
                        echo '<li>' . esc_html( $jet->name ) . ' - <a href="' . esc_url( $export_url ) . '">Export CSV</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No jets yet.</p>';
                }
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                echo '<input type="hidden" name="action" value="mbc_create_jet">';
                echo '<input type="hidden" name="dossier_id" value="' . esc_attr( $dossier->id ) . '">';
                wp_nonce_field( 'mbc_create_jet' );
                echo '<input type="text" name="name" placeholder="Jet name" required> ';
                submit_button( 'Add Jet', 'secondary', 'submit', false );
                echo '</form>';
            }
        } else {
            echo '<p>No dossiers created yet.</p>';
        }
        echo '</div>';
    }

    public static function render_events_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        $events = MBC_Events::get_events();
        echo '<div class="wrap"><h1>Events</h1>';
        echo '<h2>Create Event</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_create_event">';
        wp_nonce_field( 'mbc_create_event' );
        echo '<p><input type="text" name="name" placeholder="Event name" class="regular-text" required></p>';
        echo '<p><input type="text" name="location" placeholder="Location" class="regular-text"></p>';
        echo '<p><label>Start Date</label><br><input type="date" name="start_date"> <label>End Date</label> <input type="date" name="end_date"></p>';
        echo '<p><textarea name="notes" rows="3" cols="60" placeholder="Notes"></textarea></p>';
        submit_button( 'Create Event', 'primary', 'submit', false );
        echo '</form>';

        echo '<h2>Upcoming Events</h2>';
        if ( $events ) {
            echo '<ul>';
            foreach ( $events as $event ) {
                echo '<li>' . esc_html( $event->name ) . ' (' . esc_html( $event->start_date ) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No events yet.</p>';
        }
        echo '</div>';
    }

    public static function render_companies_page(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            return;
        }
        $companies = MBC_Companies::get_companies();
        echo '<div class="wrap"><h1>Companies</h1>';
        echo '<h2>Create Company</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_create_company">';
        wp_nonce_field( 'mbc_create_company' );
        echo '<p><input type="text" name="name" placeholder="Company name" class="regular-text" required></p>';
        echo '<p><input type="text" name="sector" placeholder="Sector" class="regular-text"></p>';
        echo '<p><textarea name="notes" rows="3" cols="60" placeholder="Notes"></textarea></p>';
        submit_button( 'Create Company', 'primary', 'submit', false );
        echo '</form>';

        echo '<h2>Companies List</h2>';
        if ( $companies ) {
            echo '<ul>';
            foreach ( $companies as $company ) {
                echo '<li>' . esc_html( $company->name ) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No companies yet.</p>';
        }
        echo '</div>';
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
        echo '<h2>Merge Candidates</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_merge_candidates">';
        wp_nonce_field( 'mbc_merge_candidates' );
        echo '<input type="number" name="primary_id" placeholder="Primary candidate ID" required> ';
        echo '<input type="number" name="secondary_id" placeholder="Secondary candidate ID" required> ';
        submit_button( 'Merge', 'primary', 'submit', false );
        echo '</form>';
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
        echo '<h2>Data Retention</h2>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="mbc_export_candidate">';
        wp_nonce_field( 'mbc_export_candidate' );
        echo '<input type="email" name="mbc_export_email" placeholder="Candidate email" required> ';
        submit_button( 'Export Candidate Data', 'secondary', 'submit', false );
        echo '</form>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:10px;">';
        echo '<input type="hidden" name="action" value="mbc_delete_candidate">';
        wp_nonce_field( 'mbc_delete_candidate' );
        echo '<input type="email" name="mbc_delete_email" placeholder="Candidate email" required> ';
        submit_button( 'Delete Candidate Data', 'delete', 'submit', false );
        echo '</form>';
        echo '</div>';
    }
}
