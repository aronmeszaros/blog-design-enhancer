<?php

if (!defined('ABSPATH')) {
    exit;
}

// Archive/listing enhancements: excerpt control and profile social links.
class BDE_Blog_Listing
{
    private BDE_Settings $settings;

    // Register listing filters.
    public function __construct(BDE_Settings $settings)
    {
        $this->settings = $settings;

        add_filter('excerpt_length', [$this, 'filter_excerpt_length'], 999);
        add_filter('get_the_excerpt', [$this, 'ensure_excerpt_for_archives'], 10, 2);
        add_filter('the_excerpt', [$this, 'append_profile_icons_to_archive_excerpt'], 20);
    }

    // Applies configured excerpt length on blog-like listings.
    public function filter_excerpt_length(int $length): int
    {
        if (!is_admin() && $this->is_blog_listing()) {
            $settings = $this->settings->get_settings();
            return (int) $settings['excerpt_length'];
        }

        return $length;
    }

    // Generates fallback excerpt when post excerpt is missing.
    public function ensure_excerpt_for_archives(string $excerpt, WP_Post $post): string
    {
        if (!$this->is_blog_listing() || $post->post_type !== 'post') {
            return $excerpt;
        }

        if (!empty($excerpt)) {
            return $excerpt;
        }

        $source = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
        $source = wp_strip_all_tags(strip_shortcodes((string) $source));

        $settings = $this->settings->get_settings();
        return wp_trim_words($source, (int) $settings['excerpt_length'], '...');
    }

    // Appends optional profile links below each archive excerpt.
    public function append_profile_icons_to_archive_excerpt(string $excerpt): string
    {
        if (!$this->should_add_profile_icons()) {
            return $excerpt;
        }

        $html = $this->build_profile_icons_html();

        if ($html === '') {
            return $excerpt;
        }

        return $excerpt . $html;
    }

    // Guard to avoid affecting admin, widgets, and non-main loops.
    private function should_add_profile_icons(): bool
    {
        return !is_admin() && in_the_loop() && is_main_query() && $this->is_blog_listing() && get_post_type() === 'post';
    }

    // Supports home, category/tag archives, and search pages.
    private function is_blog_listing(): bool
    {
        return is_home() || is_archive() || is_search();
    }

    // Builds small social profile icon group from settings URLs.
    private function build_profile_icons_html(): string
    {
        $settings = $this->settings->get_settings();

        $links = [
            'facebook' => [
                'url' => $settings['facebook_url'],
                'label' => 'Facebook',
                'text' => 'f',
            ],
            'x' => [
                'url' => $settings['x_url'],
                'label' => 'X',
                'text' => 'x',
            ],
            'linkedin' => [
                'url' => $settings['linkedin_url'],
                'label' => 'LinkedIn',
                'text' => 'in',
            ],
        ];

        $items = '';

        foreach ($links as $network => $link_data) {
            if (empty($link_data['url'])) {
                continue;
            }

            $items .= '<a class="bde-social-link bde-social-link--' . esc_attr($network) . '" href="' . esc_url($link_data['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($link_data['label']) . '"><span aria-hidden="true">' . esc_html($link_data['text']) . '</span></a>';
        }

        if ($items === '') {
            return '';
        }

        return '<div class="bde-social-icons" aria-label="Social links">' . $items . '</div>';
    }
}
