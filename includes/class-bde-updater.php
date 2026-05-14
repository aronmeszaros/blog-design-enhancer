<?php

if (!defined('ABSPATH')) {
    exit;
}

// GitHub release-based update bridge for WordPress core update checks.
class BDE_Updater
{
    private string $repository;
    private string $plugin_basename;
    private string $plugin_slug;
    private string $current_version;
    private string $api_url;
    private string $cache_key = 'bde_github_release_info';
    private string $manual_check_notice_key = 'bde_update_checked';

    public function __construct()
    {
        $this->repository = defined('BDE_GITHUB_REPOSITORY') ? trim((string) BDE_GITHUB_REPOSITORY) : '';
        $this->plugin_basename = plugin_basename(BDE_PLUGIN_FILE);
        $this->plugin_slug = dirname($this->plugin_basename);
        $this->current_version = BDE_VERSION;
        $this->api_url = $this->repository !== '' ? 'https://api.github.com/repos/' . $this->repository . '/releases/latest' : '';

        if ($this->repository === '' || strpos($this->repository, 'your-owner/') === 0) {
            return;
        }

        add_filter('site_transient_update_plugins', [$this, 'inject_update_information']);
        add_filter('plugins_api', [$this, 'provide_plugin_information'], 10, 3);
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_check_updates_link']);
        add_action('admin_post_bde_check_updates', [$this, 'handle_manual_check']);
        add_action('admin_notices', [$this, 'render_manual_check_notice']);
    }

    public function add_check_updates_link(array $links): array
    {
        if (!current_user_can('update_plugins')) {
            return $links;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=bde_check_updates'),
            'bde_check_updates'
        );

        $links[] = '<a href="' . esc_url($url) . '">Check updates</a>';

        return $links;
    }

    public function handle_manual_check(): void
    {
        if (!current_user_can('update_plugins')) {
            wp_die('Insufficient permissions.');
        }

        check_admin_referer('bde_check_updates');

        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        wp_update_plugins();

        $redirect = wp_get_referer();
        if (!is_string($redirect) || $redirect === '') {
            $redirect = admin_url('plugins.php');
        }

        $redirect = add_query_arg($this->manual_check_notice_key, '1', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    public function render_manual_check_notice(): void
    {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen === null || $screen->id !== 'plugins') {
            return;
        }

        if (empty($_GET[$this->manual_check_notice_key])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>Plugin update check completed.</p></div>';
    }

    public function inject_update_information($transient)
    {
        if (!is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if ($release === null) {
            return $transient;
        }

        if (version_compare($release['version'], $this->current_version, '<=')) {
            return $transient;
        }

        $transient->response[$this->plugin_basename] = (object) [
            'slug' => $this->plugin_slug,
            'plugin' => $this->plugin_basename,
            'new_version' => $release['version'],
            'url' => $release['html_url'],
            'package' => $release['zipball_url'],
            'tested' => '6.9',
            'requires' => '5.8',
        ];

        return $transient;
    }

    public function provide_plugin_information($result, string $action, $args)
    {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ($release === null) {
            return $result;
        }

        return (object) [
            'name' => 'Blog Design Enhancer',
            'slug' => $this->plugin_slug,
            'version' => $release['version'],
            'author' => '<a href="https://github.com/' . esc_attr($this->repository) . '">GitHub</a>',
            'homepage' => $release['html_url'],
            'download_link' => $release['zipball_url'],
            'requires' => '5.8',
            'tested' => '6.9',
            'last_updated' => $release['published_at'],
            'sections' => [
                'description' => 'Blog design and archive improvements for WordPress posts, listings, and single pages.',
                'changelog' => $release['body'] !== '' ? wpautop(esc_html($release['body'])) : 'No changelog was provided for this release.',
            ],
        ];
    }

    private function get_latest_release(): ?array
    {
        if ($this->api_url === '') {
            return null;
        }

        $cached = get_transient($this->cache_key);
        if (is_array($cached) && !empty($cached['version']) && !empty($cached['zipball_url'])) {
            return $cached;
        }

        $args = [
            'timeout' => 15,
            'redirection' => 3,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'Blog Design Enhancer/' . $this->current_version,
            ],
        ];

        $response = wp_remote_get($this->api_url, $args);
        if (is_wp_error($response)) {
            return null;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['tag_name']) || empty($data['zipball_url'])) {
            return null;
        }

        $version = ltrim((string) $data['tag_name'], 'vV');
        $package_url = (string) $data['zipball_url'];

        if (!empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                if (!is_array($asset) || empty($asset['browser_download_url'])) {
                    continue;
                }

                $asset_url = (string) $asset['browser_download_url'];
                if (substr($asset_url, -4) !== '.zip') {
                    continue;
                }

                // Prefer a deterministic ZIP asset for plugin updates.
                $package_url = $asset_url;
                break;
            }
        }

        $release = [
            'version' => $version,
            'html_url' => isset($data['html_url']) ? (string) $data['html_url'] : '',
            'zipball_url' => $package_url,
            'body' => isset($data['body']) ? (string) $data['body'] : '',
            'published_at' => isset($data['published_at']) ? (string) $data['published_at'] : '',
        ];

        set_transient($this->cache_key, $release, HOUR_IN_SECONDS);

        return $release;
    }
}