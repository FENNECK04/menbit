<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBC_Activator {
    public static function activate(): void {
        MBC_DB::install_tables();
        MBC_Security::ensure_roles();
        MBC_Security::ensure_private_uploads();
        MBC_Scheduler::schedule_recurring();

        add_option( 'mbc_settings', MBC_Security::default_settings() );
    }
}
