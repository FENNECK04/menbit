<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_DB {
    public static function init(): void {
        // Placeholder for future init logic.
    }

    public static function table( string $name ): string {
        global $wpdb;

        return $wpdb->prefix . $name;
    }

    public static function get_charset_collate(): string {
        global $wpdb;

        return $wpdb->get_charset_collate();
    }

    public static function install_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = self::get_charset_collate();

        $candidates = self::table( MBC_TABLE_CANDIDATES );
        $sectors = self::table( MBC_TABLE_SECTORS );
        $industries = self::table( MBC_TABLE_INDUSTRIES );
        $cv_versions = self::table( MBC_TABLE_CV );
        $notes = self::table( MBC_TABLE_NOTES );
        $activity = self::table( MBC_TABLE_ACTIVITY );
        $companies = self::table( MBC_TABLE_COMPANIES );
        $events = self::table( MBC_TABLE_EVENTS );
        $attendance = self::table( MBC_TABLE_EVENT_ATTENDANCE );
        $dossiers = self::table( MBC_TABLE_DOSSIERS );
        $jets = self::table( MBC_TABLE_JETS );
        $jet_candidates = self::table( MBC_TABLE_JET_CANDIDATES );
        $token_uses = self::table( MBC_TABLE_TOKEN_USES );

        dbDelta(
            "CREATE TABLE {$candidates} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(190) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                phone VARCHAR(50) NULL,
                experience_bracket VARCHAR(20) NOT NULL,
                availability_type VARCHAR(20) NOT NULL,
                availability_note TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                pipeline_stage VARCHAR(50) NOT NULL DEFAULT 'new',
                owner_user_id BIGINT UNSIGNED NULL,
                source VARCHAR(100) NOT NULL DEFAULT 'form',
                consent_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                last_activity_at DATETIME NULL,
                last_cv_at DATETIME NULL,
                last_renewal_email_at DATETIME NULL,
                renewal_state VARCHAR(20) NOT NULL DEFAULT 'none',
                duplicate_status VARCHAR(20) NOT NULL DEFAULT 'none',
                PRIMARY KEY  (id),
                UNIQUE KEY email (email),
                KEY phone (phone),
                KEY owner_user_id (owner_user_id),
                KEY status (status),
                KEY pipeline_stage (pipeline_stage)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$sectors} (
                candidate_id BIGINT UNSIGNED NOT NULL,
                sector_slug VARCHAR(100) NOT NULL,
                UNIQUE KEY candidate_sector (candidate_id, sector_slug),
                KEY sector_slug (sector_slug)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$industries} (
                candidate_id BIGINT UNSIGNED NOT NULL,
                industry_slug VARCHAR(100) NOT NULL,
                UNIQUE KEY candidate_industry (candidate_id, industry_slug),
                KEY industry_slug (industry_slug)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$cv_versions} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                candidate_id BIGINT UNSIGNED NOT NULL,
                file_url TEXT NOT NULL,
                file_path TEXT NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_hash CHAR(64) NOT NULL,
                uploaded_at DATETIME NOT NULL,
                uploaded_by VARCHAR(20) NOT NULL,
                note TEXT NULL,
                PRIMARY KEY  (id),
                KEY candidate_id (candidate_id),
                KEY file_hash (file_hash)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$notes} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                candidate_id BIGINT UNSIGNED NOT NULL,
                author_user_id BIGINT UNSIGNED NOT NULL,
                note LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY candidate_id (candidate_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$activity} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                candidate_id BIGINT UNSIGNED NOT NULL,
                actor_user_id BIGINT UNSIGNED NULL,
                action_type VARCHAR(50) NOT NULL,
                meta LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY candidate_id (candidate_id),
                KEY action_type (action_type)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$companies} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(200) NOT NULL,
                sector VARCHAR(100) NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$events} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(200) NOT NULL,
                location VARCHAR(200) NULL,
                start_date DATE NULL,
                end_date DATE NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$attendance} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id BIGINT UNSIGNED NOT NULL,
                candidate_id BIGINT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL,
                notes TEXT NULL,
                PRIMARY KEY  (id),
                KEY event_id (event_id),
                KEY candidate_id (candidate_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$dossiers} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                company_id BIGINT UNSIGNED NULL,
                event_id BIGINT UNSIGNED NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT NULL,
                created_by_user_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY company_id (company_id),
                KEY event_id (event_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$jets} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                dossier_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(200) NOT NULL,
                order_index INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY dossier_id (dossier_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$jet_candidates} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                jet_id BIGINT UNSIGNED NOT NULL,
                candidate_id BIGINT UNSIGNED NOT NULL,
                dossier_status VARCHAR(50) NOT NULL,
                feedback TEXT NULL,
                added_by_user_id BIGINT UNSIGNED NOT NULL,
                added_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY jet_candidate (jet_id, candidate_id),
                KEY jet_id (jet_id),
                KEY candidate_id (candidate_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$token_uses} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                token_hash CHAR(64) NOT NULL,
                candidate_id BIGINT UNSIGNED NOT NULL,
                used_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY token_hash (token_hash)
            ) {$charset_collate};"
        );

        $wpdb->query( "UPDATE {$candidates} SET email = LOWER(email)" );
    }
}
