<?php

namespace TurboSMTP\FreeMailSMTP\Admin;
if ( ! defined( 'ABSPATH' ) ) exit;

class Settings
{
    private $plugin_path;

    public function __construct()
    {
        $this->plugin_path = FREE_MAIL_SMTP_PATH;
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('wp_ajax_free_mail_smtp_delete_all_data', [$this, 'delete_all_plugin_data']);
    }
    public function enqueue_scripts($hook)
    {
        // Only load on settings page
        if (strpos($hook, 'free-mail-smtp-settings') === false) {
            return;
        }

        // Enqueue the CSS file
        wp_enqueue_style(
            'free-mail-smtp-settings-css',
            plugins_url('/assets/css/settings.css', FREE_MAIL_SMTP_FILE),
            [],
            FREE_MAIL_SMTP_VERSION
        );

        // Enqueue the JS file
        wp_enqueue_script(
            'free-mail-smtp-settings',
            plugins_url('/assets/js/settings.js', FREE_MAIL_SMTP_FILE),
            ['jquery'],
            FREE_MAIL_SMTP_VERSION,
            true
        );

        wp_localize_script('free-mail-smtp-settings', 'FreeMailSMTPAdminSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('free_mail_smtp_nonce_settings'),
            'adminUrl' => admin_url('admin.php?page=free-mail-smtp-settings'),
            'debug' => true
        ]);
    }
    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'free-mail-smtp'));
        }

        $from_email = get_option('free_mail_smtp_from_email');
        $from_name = get_option('free_mail_smtp_from_name');
        $enable_summary = get_option('free_mail_smtp_enable_summary', false);
        $summary_email = get_option('free_mail_smtp_summary_email', '');
        $summary_frequency = get_option('free_mail_smtp_summary_frequency', 'weekly');
        $enable_fallback = get_option('free_mail_smtp_fallback_to_wp_mail', true);
        $view_file = $this->plugin_path . '/views/admin/settings/index.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>Free Mail SMTP Settings</h1>';
            echo '<div class="notice notice-error"><p>Error: View file not found.</p></div>';
            echo '</div>';
        }
    }

    public function handle_form_submissions()
    {
        if (
            !isset($_GET['page']) || $_GET['page'] !== 'free-mail-smtp-settings' ||
            !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !isset($_POST['free_mail_smtp_nonce_settings'])
        ) {
            return;
        }

        if (!wp_verify_nonce(sanitize_key($_POST['free_mail_smtp_nonce_settings']), 'free-mail-smtp-settings')) {
            add_settings_error(
                'free_mail_smtp_messages',
                'invalid_nonce',
                __('Security check failed.', 'free-mail-smtp'),
                'error'
            );
            return;
        }
        if (isset($_POST['save_settings'])) {
            try {
                if (isset($_POST['from_email'])) {
                    update_option('free_mail_smtp_from_email', sanitize_email(wp_unslash($_POST['from_email'])));
                }
                if (isset($_POST['from_name'])) {
                    update_option('free_mail_smtp_from_name', sanitize_text_field(wp_unslash($_POST['from_name'])));
                }
                if (isset($_POST['enable_email_summary'])) {
                    update_option('free_mail_smtp_enable_summary', isset($_POST['enable_email_summary']) ? 1 : 0);
                }
                if (isset($_POST['summary_email'])) {
                    update_option('free_mail_smtp_summary_email', sanitize_email(wp_unslash($_POST['summary_email'])));
                }

                if (isset($_POST['summary_frequency'])) {
                    $allowed_frequencies = ['daily', 'weekly', 'monthly'];
                    $frequency = sanitize_text_field(wp_unslash($_POST['summary_frequency']));
                    if (in_array($frequency, $allowed_frequencies)) {
                        update_option('free_mail_smtp_summary_frequency', $frequency);
                    }
                }

                update_option('free_mail_smtp_fallback_to_wp_mail', isset($_POST['enable_fallback']) ? 1 : 0);


                add_settings_error(
                    'free_mail_smtp_messages',
                    'settings_updated',
                    __('Settings saved successfully.', 'free-mail-smtp'),
                    'success'
                );
            } catch (\Exception $e) {
                add_settings_error(
                    'free_mail_smtp_messages',
                    'save_error',
                    __('Error saving settings: ', 'free-mail-smtp') . $e->getMessage(),
                    'error'
                );
            }
        }
    }

    public function delete_all_plugin_data()
    {
        check_ajax_referer('free_mail_smtp_nonce_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'free-mail-smtp')); // Added localization
        }

        global $wpdb;

        $conditions_table = $wpdb->prefix . 'free_mail_smtp_email_router_conditions';
        $connections_table = $wpdb->prefix . 'free_mail_smtp_connections';
        $logs_table = $wpdb->prefix . 'free_mail_smtp_email_log';

        try {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query('START TRANSACTION');
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result1 = $wpdb->query("DELETE FROM ". $wpdb->prefix . 'free_mail_smtp_email_router_conditions');
            if (false === $result1) {
                // translators: %1$s is the table name, %2$s is the database error message.
                throw new \Exception(sprintf(__('Error deleting from %1$s: %2$s', 'free-mail-smtp'), $conditions_table, $wpdb->last_error));
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result2 = $wpdb->query("DELETE FROM " . $wpdb->prefix . 'free_mail_smtp_connections');
            if (false === $result2) {
                // translators: %1$s is the table name, %2$s is the database error message.
                throw new \Exception(sprintf(__('Error deleting from %1$s: %2$s', 'free-mail-smtp'), $connections_table, $wpdb->last_error));
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $result3 = $wpdb->query("DELETE FROM " . $wpdb->prefix . 'free_mail_smtp_email_log');
            if (false === $result3) {
                // translators: %1$s is the table name, %2$s is the database error message.
                throw new \Exception(sprintf(__('Error deleting from %1$s: %2$s', 'free-mail-smtp'), $logs_table, $wpdb->last_error));
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query('COMMIT');

            $options = [
                'free_mail_smtp_from_email',
                'free_mail_smtp_from_name',
                'free_mail_smtp_enable_summary',
                'free_mail_smtp_summary_email',
                'free_mail_smtp_summary_frequency',
                'free_mail_smtp_fallback_to_wp_mail',
                'free_mail_smtp_gmail_access_token',
                'free_mail_smtp_gmail_refresh_token',
                'free_mail_smtp_outlook_refresh_token',
                'free_mail_smtp_outlook_access_token',
                'free_mail_smtp_import_easysmtp_notice_dismissed',
                'free_mail_smtp_import_wpmail_notice_dismissed',
                'free_mail_smtp_retention_duration'
            ];

            foreach ($options as $option) {
                delete_option($option);
            }

            if (class_exists('TurboSMTP\FreeMailSMTP\Cron\CronManager') && method_exists(\TurboSMTP\FreeMailSMTP\Cron\CronManager::class, 'get_instance')) {
                 \TurboSMTP\FreeMailSMTP\Cron\CronManager::get_instance()->deactivate_crons();
            }

            wp_send_json_success(__('All plugin data has been deleted successfully.', 'free-mail-smtp')); // Added localization

        } catch (\Exception $e) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query('ROLLBACK');
            // translators: %s is the exception error message.
            wp_send_json_error(sprintf(__('Error deleting plugin data: %s', 'free-mail-smtp'), $e->getMessage())); // Added localization
        }
    }
}
