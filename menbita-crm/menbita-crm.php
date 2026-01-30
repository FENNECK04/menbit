<?php
/**
 * Plugin Name: Menbita CRM
 * Description: Internal ATS/CRM for Menbita candidate management.
 * Version: 0.1.0
 * Author: Menbita
 * Text Domain: menbita-crm
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MBC_VERSION', '0.1.0' );
define( 'MBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBC_PRIVATE_UPLOAD_DIR', 'menbita-crm-private' );

define( 'MBC_CAPABILITY', 'manage_menbita_crm' );

define( 'MBC_TABLE_CANDIDATES', 'mbc_candidates' );
define( 'MBC_TABLE_SECTORS', 'mbc_candidate_sectors' );
define( 'MBC_TABLE_INDUSTRIES', 'mbc_candidate_industries' );
define( 'MBC_TABLE_CV', 'mbc_cv_versions' );
define( 'MBC_TABLE_NOTES', 'mbc_notes' );
define( 'MBC_TABLE_ACTIVITY', 'mbc_activity_log' );
define( 'MBC_TABLE_COMPANIES', 'mbc_companies' );
define( 'MBC_TABLE_EVENTS', 'mbc_events' );
define( 'MBC_TABLE_EVENT_ATTENDANCE', 'mbc_event_attendance' );
define( 'MBC_TABLE_DOSSIERS', 'mbc_dossiers' );
define( 'MBC_TABLE_JETS', 'mbc_jets' );
define( 'MBC_TABLE_JET_CANDIDATES', 'mbc_jet_candidates' );
define( 'MBC_TABLE_TOKEN_USES', 'mbc_token_uses' );

require_once MBC_PLUGIN_PATH . 'includes/class-mbc-db.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-activator.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-security.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-tokens.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-candidates.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-exports.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-merge.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-dossiers.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-events.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-companies.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-scheduler.php';
require_once MBC_PLUGIN_PATH . 'includes/class-mbc-admin.php';

register_activation_hook( __FILE__, array( 'MBC_Activator', 'activate' ) );

add_action( 'plugins_loaded', array( 'MBC_DB', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Scheduler', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Admin', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Candidates', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Exports', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Merge', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Dossiers', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Events', 'init' ) );
add_action( 'plugins_loaded', array( 'MBC_Companies', 'init' ) );
