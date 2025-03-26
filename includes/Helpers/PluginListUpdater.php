<?php

namespace FreeMailSMTP\Helpers;

class PluginListUpdater {

    /**
     * Updates the option storing the list of active plugins.
     *
     * @return void
     */
    public function updateActivePluginsOption() {
        if ( ! function_exists( 'get_plugins' ) ) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins     = [];
        $all_plugins = wp_list_pluck(
            array_intersect_key( get_plugins(), array_flip( (array) get_option( 'active_plugins' ) ) ),
            'Name'
        );

        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            $plugins[] = [
                'name' => $plugin_data,
                'path' => dirname( $plugin_file ),
            ];
        }

        $mu_plugins = get_mu_plugins();
        foreach ( $mu_plugins as $plugin_file => $plugin_data ) {
            $plugins[] = [
                'name' => $plugin_data,
                'path' => dirname( $plugin_file ),
            ];
        }

        try {
            $theme = wp_get_theme();
            $theme_name = $theme->get( 'Name' );
            $plugins[] = [
                'name' => 'Theme: ' . $theme_name,
                'path' => $theme_name,
            ];
        } catch ( \Exception $e ) {
            error_log( 'Free Mail SMTP: ' . $e->getMessage() );
        }

        $plugins[] = [
            'name' => 'Core WP',
            'path' => 'Core WP',
        ];
        update_option( 'free_mail_smtp_active_plugins_list', $plugins );
    }
}