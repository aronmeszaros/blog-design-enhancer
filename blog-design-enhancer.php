<?php
/**
 * Plugin Name: Blog Design Enhancer
 * Description: Improves blog/archive and single-post UX with excerpts, CTA modules, TOC, share tools, related posts, and custom CSS/JS.
 * Version: 1.5.0
 * Author: Aron Meszaros
 * License: GPL-2.0-or-later
 * Text Domain: blog-design-enhancer
 * Update URI: https://github.com/aronmeszaros/blog-design-enhancer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-wide constants used by all modular classes.
define('BDE_VERSION', '1.5.0');
define('BDE_PLUGIN_FILE', __FILE__);
define('BDE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BDE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BDE_GITHUB_REPOSITORY', 'aronmeszaros/blog-design-enhancer');

// Central class loader.
require_once BDE_PLUGIN_PATH . 'includes/class-bde-plugin.php';

// Public accessor for the singleton plugin instance.
function bde_plugin(): BDE_Plugin
{
    return BDE_Plugin::instance();
}

// Boot plugin.
bde_plugin();
