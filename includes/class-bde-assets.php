<?php

if (!defined('ABSPATH')) {
    exit;
}

// Enqueues plugin frontend assets and applies optional custom CSS/JS.
class BDE_Assets
{
    private BDE_Settings $settings;

    // Register frontend enqueue hook.
    public function __construct(BDE_Settings $settings)
    {
        $this->settings = $settings;
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    // Loads static assets first and then user-provided inline code.
    public function enqueue_frontend_assets(): void
    {
        wp_enqueue_style(
            'bde-frontend',
            BDE_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            BDE_VERSION
        );

        wp_enqueue_script(
            'bde-frontend',
            BDE_PLUGIN_URL . 'assets/js/frontend.js',
            [],
            BDE_VERSION,
            true
        );

        $settings = $this->settings->get_settings();

        if (!empty($settings['custom_css'])) {
            wp_add_inline_style('bde-frontend', $settings['custom_css']);
        }

        if (!empty($settings['custom_js'])) {
            wp_add_inline_script('bde-frontend', $settings['custom_js']);
        }
    }
}
