# Menbita CRM

Menbita CRM is an internal ATS/CRM WordPress plugin for managing candidate CV submissions, deduplication, pipelines, dossiers, jets, events, exports, and renewal emails.

## Installation
1. Copy the `menbita-crm` folder into `wp-content/plugins/`.
2. Activate **Menbita CRM** from the WordPress admin.
3. Create a page containing the shortcode `[menbita_cv_form]` for public submissions.
4. Create a page containing the shortcode `[menbita_cv_update]` for secure CV updates (token required).

## Cron recommendations
- Action Scheduler is used if available (WooCommerce or Action Scheduler plugin). Otherwise, WP-Cron will be used.
- Ensure WP-Cron or system cron triggers WordPress regularly to send renewal emails.

## Configuration
- Admin settings are located under **Menbita CRM → Settings**.
- Configure email templates, token expiration, renewal rules, and upload limits.

## Roles & capabilities
- Administrators automatically get the `manage_menbita_crm` capability on activation.
- Access to Menbita CRM pages is restricted to users with this capability.

## Troubleshooting
- Use **Menbita CRM → Settings** to view the self-check output for table and scheduler status.
- Make sure uploads are writable; private CVs are stored in `wp-content/uploads/menbita-crm-private/`.

## Screenshots
Add screenshots to `assets/screenshots/` when available:
- `assets/screenshots/01-candidates.png`
- `assets/screenshots/02-candidate-detail.png`
- `assets/screenshots/03-dossiers.png`
