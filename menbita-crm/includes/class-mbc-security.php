<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Security {
    public static function ensure_roles(): void {
        $role = get_role( 'administrator' );
        if ( $role && ! $role->has_cap( MBC_CAPABILITY ) ) {
            $role->add_cap( MBC_CAPABILITY );
        }
    }

    public static function default_settings(): array {
        return array(
            'from_name' => get_bloginfo( 'name' ),
            'from_email' => get_bloginfo( 'admin_email' ),
            'confirmation_subject' => 'Your CV was received',
            'confirmation_body' => "Hello {first_name},\n\nYour email is registered and your CV was transmitted.",
            'renewal_subject' => 'Time to renew your CV',
            'renewal_body' => "Hello {first_name},\n\nPlease renew your CV if you are still interested in opportunities in Morocco.",
            'reminder_subject' => 'Reminder: renew your CV',
            'reminder_body' => "Hello {first_name},\n\nWe have not received an updated CV yet.",
            'token_expiration_days' => 14,
            'renewal_days' => 365,
            'renewal_reminder_days' => 7,
            'renewal_inactive_days' => 21,
            'max_upload_mb' => 5,
            'allowed_mimes' => array( 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
            'gdpr_consent_text' => 'I consent to Menbita processing my data for recruitment purposes.',
        );
    }

    public static function ensure_private_uploads(): void {
        $upload_dir = wp_upload_dir();
        $private_dir = trailingslashit( $upload_dir['basedir'] ) . MBC_PRIVATE_UPLOAD_DIR;
        if ( ! file_exists( $private_dir ) ) {
            wp_mkdir_p( $private_dir );
        }

        $htaccess = trailingslashit( $private_dir ) . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Deny from all\n" );
        }

        $index = trailingslashit( $private_dir ) . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden.\n" );
        }
    }

    public static function current_user_can_manage(): bool {
        return current_user_can( MBC_CAPABILITY );
    }

    public static function rate_limit_key( string $email, string $ip ): string {
        return 'mbc_rl_' . md5( $email . '|' . $ip );
    }

    public static function is_rate_limited( string $email, string $ip ): bool {
        $key = self::rate_limit_key( $email, $ip );
        return (bool) get_transient( $key );
    }

    public static function set_rate_limit( string $email, string $ip ): void {
        $key = self::rate_limit_key( $email, $ip );
        set_transient( $key, 1, MINUTE_IN_SECONDS * 5 );
    }
}
