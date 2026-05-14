<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once BDE_PLUGIN_PATH . 'includes/class-bde-settings.php';
require_once BDE_PLUGIN_PATH . 'includes/class-bde-assets.php';
require_once BDE_PLUGIN_PATH . 'includes/class-bde-blog-listing.php';
require_once BDE_PLUGIN_PATH . 'includes/class-bde-updater.php';
require_once BDE_PLUGIN_PATH . 'includes/class-bde-single-post.php';

// Main orchestrator that wires all feature modules together.
class BDE_Plugin
{
    private static ?BDE_Plugin $instance = null;

    // Modular services.
    private BDE_Settings $settings;
    private BDE_Assets $assets;
    private BDE_Blog_Listing $blog_listing;
    private BDE_Updater $updater;
    private BDE_Single_Post $single_post;

    // Initialize feature modules once during plugin bootstrap.
    private function __construct()
    {
        $this->settings = new BDE_Settings();
        $this->assets = new BDE_Assets($this->settings);
        $this->blog_listing = new BDE_Blog_Listing($this->settings);
        $this->updater = new BDE_Updater();
        $this->single_post = new BDE_Single_Post($this->settings);
    }

    // Singleton accessor used by bootstrap function.
    public static function instance(): BDE_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
