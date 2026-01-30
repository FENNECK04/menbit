<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Tokens {
    public static function generate_token( int $candidate_id, string $email ): string {
        $issued_at = time();
        $settings = get_option( 'mbc_settings', MBC_Security::default_settings() );
        $expires_at = $issued_at + ( absint( $settings['token_expiration_days'] ) * DAY_IN_SECONDS );
        $nonce = wp_generate_password( 12, false );
        $payload = array(
            'candidate_id' => $candidate_id,
            'email_hash' => hash( 'sha256', strtolower( $email ) ),
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
            'nonce' => $nonce,
        );
        $json = wp_json_encode( $payload );
        $signature = hash_hmac( 'sha256', $json, wp_salt( 'mbc_token' ) );
        return base64_encode( $json ) . '.' . $signature;
    }

    public static function validate_token( string $token ): ?array {
        $parts = explode( '.', $token );
        if ( 2 !== count( $parts ) ) {
            return null;
        }
        $payload_json = base64_decode( $parts[0] );
        $signature = $parts[1];
        $expected = hash_hmac( 'sha256', $payload_json, wp_salt( 'mbc_token' ) );
        if ( ! hash_equals( $expected, $signature ) ) {
            return null;
        }
        $payload = json_decode( $payload_json, true );
        if ( empty( $payload['candidate_id'] ) || empty( $payload['expires_at'] ) || empty( $payload['email_hash'] ) ) {
            return null;
        }
        if ( time() > absint( $payload['expires_at'] ) ) {
            return null;
        }

        if ( self::is_token_used( $signature ) ) {
            return null;
        }

        return $payload;
    }

    public static function mark_token_used( string $token_signature, int $candidate_id ): void {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_TOKEN_USES );
        $wpdb->insert(
            $table,
            array(
                'token_hash' => $token_signature,
                'candidate_id' => $candidate_id,
                'used_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s' )
        );
    }

    private static function is_token_used( string $token_signature ): bool {
        global $wpdb;
        $table = MBC_DB::table( MBC_TABLE_TOKEN_USES );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE token_hash = %s", $token_signature ) );
        return ! empty( $exists );
    }
}
