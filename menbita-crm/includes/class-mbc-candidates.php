<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Candidates {
    public static function init(): void {
        add_shortcode( 'menbita_cv_form', array( __CLASS__, 'render_form_shortcode' ) );
        add_shortcode( 'menbita_cv_update', array( __CLASS__, 'render_update_shortcode' ) );
        add_action( 'init', array( __CLASS__, 'handle_form_submission' ) );
        add_action( 'init', array( __CLASS__, 'handle_update_submission' ) );
        add_action( 'admin_post_mbc_download_cv', array( __CLASS__, 'handle_download_cv' ) );
    }

    public static function render_form_shortcode(): string {
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $message = '';
        if ( isset( $_GET['mbc_submitted'] ) ) {
            $message = '<div class="mbc-confirmation">Your email is registered and your CV was transmitted.</div>';
        }
        ob_start();
        ?>
        <form class="mbc-cv-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mbc_submit_cv', 'mbc_nonce' ); ?>
            <input type="hidden" name="mbc_form" value="1">
            <p>
                <label for="mbc_last_name">Last name</label>
                <input type="text" name="mbc_last_name" id="mbc_last_name" required>
            </p>
            <p>
                <label for="mbc_first_name">First name</label>
                <input type="text" name="mbc_first_name" id="mbc_first_name" required>
            </p>
            <p>
                <label for="mbc_email">Email</label>
                <input type="email" name="mbc_email" id="mbc_email" required>
            </p>
            <p>
                <label for="mbc_phone">Phone</label>
                <input type="text" name="mbc_phone" id="mbc_phone" required>
            </p>
            <p>
                <label for="mbc_experience">Experience bracket</label>
                <select name="mbc_experience" id="mbc_experience" required>
                    <option value="student">Student</option>
                    <option value="0_3">0-3</option>
                    <option value="3_8">3-8</option>
                    <option value="8_plus">+8</option>
                </select>
            </p>
            <p>
                <label for="mbc_availability">Availability</label>
                <select name="mbc_availability" id="mbc_availability" required>
                    <option value="asap">ASAP</option>
                    <option value="notice">Notice period</option>
                    <option value="other">Other</option>
                </select>
            </p>
            <p>
                <label for="mbc_availability_note">Availability note</label>
                <input type="text" name="mbc_availability_note" id="mbc_availability_note">
            </p>
            <p>
                <label>Sectors</label>
                <?php foreach ( self::sectors_list() as $slug => $label ) : ?>
                    <label><input type="checkbox" name="mbc_sectors[]" value="<?php echo esc_attr( $slug ); ?>"> <?php echo esc_html( $label ); ?></label><br>
                <?php endforeach; ?>
                <input type="text" name="mbc_sector_other" placeholder="Other sector">
            </p>
            <p>
                <label>Industries</label>
                <?php foreach ( self::industries_list() as $slug => $label ) : ?>
                    <label><input type="checkbox" name="mbc_industries[]" value="<?php echo esc_attr( $slug ); ?>"> <?php echo esc_html( $label ); ?></label><br>
                <?php endforeach; ?>
                <input type="text" name="mbc_industry_other" placeholder="Other industry">
            </p>
            <p>
                <label for="mbc_cv">CV upload</label>
                <input type="file" name="mbc_cv" id="mbc_cv" required>
            </p>
            <p>
                <label><input type="checkbox" name="mbc_consent" value="1" required> <?php echo esc_html( $settings['gdpr_consent_text'] ); ?></label>
            </p>
            <p class="mbc-honeypot" style="display:none;">
                <label>Leave this field empty</label>
                <input type="text" name="mbc_company">
            </p>
            <button type="submit">Submit CV</button>
        </form>
        <?php
        echo $message;
        return ob_get_clean();
    }

    public static function render_update_shortcode( array $atts ): string {
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        if ( empty( $token ) ) {
            return '<p>Invalid token.</p>';
        }
        $payload = MBC_Tokens::validate_token( $token );
        if ( empty( $payload ) ) {
            return '<p>Token is invalid or expired.</p>';
        }
        $candidate = self::get_candidate( absint( $payload['candidate_id'] ) );
        if ( ! $candidate ) {
            return '<p>Candidate not found.</p>';
        }
        ob_start();
        ?>
        <form class="mbc-cv-update" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mbc_update_cv', 'mbc_update_nonce' ); ?>
            <input type="hidden" name="mbc_update" value="1">
            <input type="hidden" name="mbc_token" value="<?php echo esc_attr( $token ); ?>">
            <p>
                <label for="mbc_experience">Experience bracket</label>
                <select name="mbc_experience" id="mbc_experience" required>
                    <option value="student" <?php selected( $candidate->experience_bracket, 'student' ); ?>>Student</option>
                    <option value="0_3" <?php selected( $candidate->experience_bracket, '0_3' ); ?>>0-3</option>
                    <option value="3_8" <?php selected( $candidate->experience_bracket, '3_8' ); ?>>3-8</option>
                    <option value="8_plus" <?php selected( $candidate->experience_bracket, '8_plus' ); ?>>+8</option>
                </select>
            </p>
            <p>
                <label for="mbc_availability">Availability</label>
                <select name="mbc_availability" id="mbc_availability" required>
                    <option value="asap" <?php selected( $candidate->availability_type, 'asap' ); ?>>ASAP</option>
                    <option value="notice" <?php selected( $candidate->availability_type, 'notice' ); ?>>Notice period</option>
                    <option value="other" <?php selected( $candidate->availability_type, 'other' ); ?>>Other</option>
                </select>
            </p>
            <p>
                <label for="mbc_availability_note">Availability note</label>
                <input type="text" name="mbc_availability_note" id="mbc_availability_note" value="<?php echo esc_attr( $candidate->availability_note ); ?>">
            </p>
            <p>
                <label>Sectors</label>
                <?php foreach ( self::sectors_list() as $slug => $label ) : ?>
                    <label><input type="checkbox" name="mbc_sectors[]" value="<?php echo esc_attr( $slug ); ?>"> <?php echo esc_html( $label ); ?></label><br>
                <?php endforeach; ?>
            </p>
            <p>
                <label>Industries</label>
                <?php foreach ( self::industries_list() as $slug => $label ) : ?>
                    <label><input type="checkbox" name="mbc_industries[]" value="<?php echo esc_attr( $slug ); ?>"> <?php echo esc_html( $label ); ?></label><br>
                <?php endforeach; ?>
            </p>
            <p>
                <label for="mbc_cv">Upload new CV</label>
                <input type="file" name="mbc_cv" id="mbc_cv" required>
            </p>
            <button type="submit">Update CV</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function handle_form_submission(): void {
        if ( empty( $_POST['mbc_form'] ) ) {
            return;
        }
        if ( empty( $_POST['mbc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbc_nonce'] ) ), 'mbc_submit_cv' ) ) {
            return;
        }
        if ( ! empty( $_POST['mbc_company'] ) ) {
            return;
        }
        if ( empty( $_POST['mbc_consent'] ) ) {
            return;
        }

        $email = sanitize_email( wp_unslash( $_POST['mbc_email'] ?? '' ) );
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ( MBC_Security::is_rate_limited( $email, $ip ) ) {
            return;
        }
        MBC_Security::set_rate_limit( $email, $ip );

        $data = self::sanitize_form_data();
        if ( empty( $data['email'] ) || empty( $data['first_name'] ) || empty( $data['last_name'] ) ) {
            return;
        }

        $candidate_id = self::upsert_candidate( $data );
        if ( ! $candidate_id ) {
            return;
        }

        $upload = self::handle_cv_upload( 'mbc_cv' );
        if ( $upload ) {
            self::insert_cv_version( $candidate_id, $upload, 'candidate' );
            self::update_candidate_last_cv( $candidate_id );
            self::check_duplicate_signals( $candidate_id, $data, $upload['file_hash'] );
        }

        self::log_activity( $candidate_id, 'created', array( 'source' => 'form' ) );
        self::send_confirmation_email( $candidate_id );

        $redirect = add_query_arg( 'mbc_submitted', '1', wp_get_referer() ?: home_url() );
        wp_safe_redirect( $redirect );
        exit;
    }

    public static function handle_update_submission(): void {
        if ( empty( $_POST['mbc_update'] ) ) {
            return;
        }
        if ( empty( $_POST['mbc_update_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbc_update_nonce'] ) ), 'mbc_update_cv' ) ) {
            return;
        }
        $token = sanitize_text_field( wp_unslash( $_POST['mbc_token'] ?? '' ) );
        $parts = explode( '.', $token );
        $payload = MBC_Tokens::validate_token( $token );
        if ( empty( $payload ) || 2 !== count( $parts ) ) {
            return;
        }
        $candidate_id = absint( $payload['candidate_id'] );
        $candidate = self::get_candidate( $candidate_id );
        if ( ! $candidate ) {
            return;
        }
        if ( ! hash_equals( $payload['email_hash'], hash( 'sha256', strtolower( $candidate->email ) ) ) ) {
            return;
        }

        $data = self::sanitize_form_data( true );
        self::update_candidate_fields( $candidate_id, $data );

        $upload = self::handle_cv_upload( 'mbc_cv' );
        if ( $upload ) {
            self::insert_cv_version( $candidate_id, $upload, 'candidate' );
            self::update_candidate_last_cv( $candidate_id );
            self::log_activity( $candidate_id, 'cv_uploaded', array( 'source' => 'update' ) );
        }

        MBC_Tokens::mark_token_used( $parts[1], $candidate_id );
        self::send_confirmation_email( $candidate_id );

        $redirect = add_query_arg( 'mbc_updated', '1', wp_get_referer() ?: home_url() );
        wp_safe_redirect( $redirect );
        exit;
    }

    public static function handle_download_cv(): void {
        if ( ! MBC_Security::current_user_can_manage() ) {
            wp_die( esc_html__( 'Unauthorized', 'menbita-crm' ) );
        }
        if ( empty( $_GET['cv_id'] ) || empty( $_GET['_wpnonce'] ) ) {
            wp_die( esc_html__( 'Invalid request', 'menbita-crm' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mbc_download_cv' ) ) {
            wp_die( esc_html__( 'Invalid nonce', 'menbita-crm' ) );
        }
        $cv_id = absint( $_GET['cv_id'] );
        $cv = self::get_cv_version( $cv_id );
        if ( ! $cv || ! file_exists( $cv->file_path ) ) {
            wp_die( esc_html__( 'File not found', 'menbita-crm' ) );
        }
        header( 'Content-Type: ' . $cv->mime_type );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $cv->original_filename ) . '"' );
        readfile( $cv->file_path );
        exit;
    }

    private static function sanitize_form_data( bool $update_only = false ): array {
        $data = array(
            'first_name' => sanitize_text_field( wp_unslash( $_POST['mbc_first_name'] ?? '' ) ),
            'last_name' => sanitize_text_field( wp_unslash( $_POST['mbc_last_name'] ?? '' ) ),
            'email' => strtolower( sanitize_email( wp_unslash( $_POST['mbc_email'] ?? '' ) ) ),
            'phone' => sanitize_text_field( wp_unslash( $_POST['mbc_phone'] ?? '' ) ),
            'experience_bracket' => sanitize_text_field( wp_unslash( $_POST['mbc_experience'] ?? '' ) ),
            'availability_type' => sanitize_text_field( wp_unslash( $_POST['mbc_availability'] ?? '' ) ),
            'availability_note' => sanitize_text_field( wp_unslash( $_POST['mbc_availability_note'] ?? '' ) ),
            'sectors' => array_map( 'sanitize_text_field', wp_unslash( $_POST['mbc_sectors'] ?? array() ) ),
            'industries' => array_map( 'sanitize_text_field', wp_unslash( $_POST['mbc_industries'] ?? array() ) ),
            'sector_other' => sanitize_text_field( wp_unslash( $_POST['mbc_sector_other'] ?? '' ) ),
            'industry_other' => sanitize_text_field( wp_unslash( $_POST['mbc_industry_other'] ?? '' ) ),
        );

        if ( ! empty( $data['sector_other'] ) ) {
            $data['sectors'][] = 'other:' . $data['sector_other'];
        }
        if ( ! empty( $data['industry_other'] ) ) {
            $data['industries'][] = 'other:' . $data['industry_other'];
        }

        if ( $update_only ) {
            unset( $data['first_name'], $data['last_name'], $data['email'], $data['phone'] );
        }

        return $data;
    }

    private static function upsert_candidate( array $data ): int {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", $data['email'] ) );
        $now = current_time( 'mysql' );

        if ( $existing ) {
            $wpdb->update(
                $table,
                array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'experience_bracket' => $data['experience_bracket'],
                    'availability_type' => $data['availability_type'],
                    'availability_note' => $data['availability_note'],
                    'updated_at' => $now,
                    'last_activity_at' => $now,
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );

            self::sync_taxonomy_table( $existing->id, $data['sectors'], MBC_TABLE_SECTORS, 'sector_slug' );
            self::sync_taxonomy_table( $existing->id, $data['industries'], MBC_TABLE_INDUSTRIES, 'industry_slug' );

            return (int) $existing->id;
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'experience_bracket' => $data['experience_bracket'],
                'availability_type' => $data['availability_type'],
                'availability_note' => $data['availability_note'],
                'status' => 'active',
                'pipeline_stage' => 'new',
                'owner_user_id' => null,
                'source' => 'form',
                'consent_at' => current_time( 'mysql' ),
                'created_at' => $now,
                'updated_at' => $now,
                'last_activity_at' => $now,
                'last_cv_at' => null,
                'last_renewal_email_at' => null,
                'renewal_state' => 'none',
                'duplicate_status' => 'none',
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $inserted ) {
            $candidate_id = (int) $wpdb->insert_id;
            self::sync_taxonomy_table( $candidate_id, $data['sectors'], MBC_TABLE_SECTORS, 'sector_slug' );
            self::sync_taxonomy_table( $candidate_id, $data['industries'], MBC_TABLE_INDUSTRIES, 'industry_slug' );
            return $candidate_id;
        }

        return 0;
    }

    private static function update_candidate_fields( int $candidate_id, array $data ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $wpdb->update(
            $table,
            array(
                'experience_bracket' => $data['experience_bracket'],
                'availability_type' => $data['availability_type'],
                'availability_note' => $data['availability_note'],
                'updated_at' => current_time( 'mysql' ),
                'last_activity_at' => current_time( 'mysql' ),
                'renewal_state' => 'none',
            ),
            array( 'id' => $candidate_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        self::sync_taxonomy_table( $candidate_id, $data['sectors'], MBC_TABLE_SECTORS, 'sector_slug' );
        self::sync_taxonomy_table( $candidate_id, $data['industries'], MBC_TABLE_INDUSTRIES, 'industry_slug' );
    }

    private static function sync_taxonomy_table( int $candidate_id, array $values, string $table_name, string $column ): void {
        global $wpdb;
        $table = MBC_DB::table( $table_name );
        $wpdb->delete( $table, array( 'candidate_id' => $candidate_id ), array( '%d' ) );
        foreach ( $values as $value ) {
            if ( empty( $value ) ) {
                continue;
            }
            $wpdb->insert(
                $table,
                array(
                    'candidate_id' => $candidate_id,
                    $column => $value,
                ),
                array( '%d', '%s' )
            );
        }
    }

    private static function handle_cv_upload( string $field ): ?array {
        if ( empty( $_FILES[ $field ]['name'] ) ) {
            return null;
        }

        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $file = $_FILES[ $field ];
        $max_bytes = absint( $settings['max_upload_mb'] ) * MB_IN_BYTES;

        if ( $file['size'] > $max_bytes ) {
            return null;
        }

        $allowed_mimes = $settings['allowed_mimes'];
        $type = wp_check_filetype( $file['name'] );
        if ( empty( $type['type'] ) || ! in_array( $type['type'], $allowed_mimes, true ) ) {
            return null;
        }

        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        $private_dir = trailingslashit( $upload_dir['basedir'] ) . MBC_PRIVATE_UPLOAD_DIR;
        $filename = wp_unique_filename( $private_dir, basename( $upload['file'] ) );
        $destination = trailingslashit( $private_dir ) . $filename;
        rename( $upload['file'], $destination );

        $file_hash = hash_file( 'sha256', $destination );

        return array(
            'file_path' => $destination,
            'file_url' => '',
            'original_filename' => sanitize_file_name( $file['name'] ),
            'mime_type' => $type['type'],
            'file_hash' => $file_hash,
        );
    }

    private static function insert_cv_version( int $candidate_id, array $upload, string $uploaded_by ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CV );
        $wpdb->insert(
            $table,
            array(
                'candidate_id' => $candidate_id,
                'file_url' => $upload['file_url'],
                'file_path' => $upload['file_path'],
                'original_filename' => $upload['original_filename'],
                'mime_type' => $upload['mime_type'],
                'file_hash' => $upload['file_hash'],
                'uploaded_at' => current_time( 'mysql' ),
                'uploaded_by' => $uploaded_by,
                'note' => '',
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    private static function update_candidate_last_cv( int $candidate_id ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $wpdb->update(
            $table,
            array(
                'last_cv_at' => current_time( 'mysql' ),
                'last_activity_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $candidate_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    private static function check_duplicate_signals( int $candidate_id, array $data, string $file_hash ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );

        $phone_match = null;
        if ( ! empty( $data['phone'] ) ) {
            $phone_match = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE phone = %s AND id != %d", $data['phone'], $candidate_id ) );
        }

        $hash_match = $wpdb->get_var( $wpdb->prepare( "SELECT candidate_id FROM " . MBC_DB::table( MBC_TABLE_CV ) . " WHERE file_hash = %s AND candidate_id != %d", $file_hash, $candidate_id ) );

        if ( $phone_match || $hash_match ) {
            $wpdb->update(
                $table,
                array( 'duplicate_status' => 'suspect' ),
                array( 'id' => $candidate_id ),
                array( '%s' ),
                array( '%d' )
            );
            $confidence = $hash_match ? 'high' : 'medium';
            self::log_activity( $candidate_id, 'duplicate_suspect', array( 'confidence' => $confidence, 'phone_match' => $phone_match, 'hash_match' => $hash_match ) );
        }
    }

    public static function send_confirmation_email( int $candidate_id ): void {
        $candidate = self::get_candidate( $candidate_id );
        if ( ! $candidate ) {
            return;
        }
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $token = MBC_Tokens::generate_token( $candidate_id, $candidate->email );
        $update_link = add_query_arg( 'token', rawurlencode( $token ), home_url( '/update-cv/' ) );

        $subject = str_replace( '{first_name}', $candidate->first_name, $settings['confirmation_subject'] );
        $body = str_replace( '{first_name}', $candidate->first_name, $settings['confirmation_body'] );
        $body .= "\n\nUpdate your CV: {$update_link}";

        wp_mail(
            $candidate->email,
            $subject,
            $body,
            array( 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>' )
        );

        self::log_activity( $candidate_id, 'email_sent', array( 'type' => 'confirmation' ) );
    }

    public static function get_candidate( int $candidate_id ): ?object {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CANDIDATES );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $candidate_id ) );
        return $row ?: null;
    }

    public static function get_cv_version( int $cv_id ): ?object {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_CV );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $cv_id ) );
        return $row ?: null;
    }

    public static function log_activity( int $candidate_id, string $action, array $meta = array(), ?int $actor_user_id = null ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_ACTIVITY );
        $wpdb->insert(
            $table,
            array(
                'candidate_id' => $candidate_id,
                'actor_user_id' => $actor_user_id,
                'action_type' => $action,
                'meta' => wp_json_encode( $meta ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );
    }

    public static function sectors_list(): array {
        return array(
            'finance' => 'Finance',
            'audit' => 'Audit/Controlling',
            'strategy' => 'Strategy/Management/Transformation',
            'marketing' => 'Marketing/Comms',
            'engineering' => 'Engineering',
            'it' => 'IT/Tech',
            'data' => 'Data',
            'legal' => 'Legal',
            'sales' => 'Sales/BD',
            'hr' => 'HR/Recruitment',
            'pm' => 'Project Manager',
            'other' => 'Other',
        );
    }

    public static function industries_list(): array {
        return array(
            'banking' => 'Banking/Insurance',
            'energy' => 'Energy',
            'construction' => 'Construction/BTP',
            'agrifood' => 'Agri-food',
            'health' => 'Health',
            'education' => 'Education',
            'public' => 'Public sector',
            'it' => 'IT & Tech',
            'other' => 'Other',
        );
    }
}
