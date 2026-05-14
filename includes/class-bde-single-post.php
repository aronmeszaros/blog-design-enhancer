<?php

if (!defined('ABSPATH')) {
    exit;
}

// Single post enhancements for SEO/UX: breadcrumb, TOC, featured image, sharing, and related posts.
class BDE_Single_Post
{
    private BDE_Settings $settings;

    // Register hooks for post-thumbnails support and content enhancements.
    public function __construct(BDE_Settings $settings)
    {
        $this->settings = $settings;

        add_action('after_setup_theme', [$this, 'ensure_thumbnail_support']);
        add_filter('the_content', [$this, 'enhance_single_content'], 12);
        add_filter('get_the_archive_title', [$this, 'prefix_archive_title_with_breadcrumb'], 20);
    }

    // Forces thumbnail support for posts when a theme does not declare it.
    public function ensure_thumbnail_support(): void
    {
        add_theme_support('post-thumbnails', ['post']);
    }

    // Main content transformer for single post templates.
    public function enhance_single_content(string $content): string
    {
        if (!$this->should_enhance_single()) {
            return $content;
        }

        [$content_with_ids, $headings] = $this->inject_heading_ids($content);

        $breadcrumb = $this->build_single_breadcrumb();
        $featured_image = $this->build_featured_image_html();
        $toc = $this->build_toc($headings);
        $bottom_modules = $this->build_bottom_modules();

        return $breadcrumb . $featured_image . $toc . $content_with_ids . $bottom_modules;
    }

    // Adds lightweight breadcrumb prefix to archive category titles.
    public function prefix_archive_title_with_breadcrumb(string $title): string
    {
        if (is_admin() || !is_archive() || !is_category()) {
            return $title;
        }

        $breadcrumb = '<span class="bde-inline-breadcrumb"><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Domov', 'blog-design-enhancer') . '</a> / <span>' . esc_html__('Clanky', 'blog-design-enhancer') . '</span></span>';

        return $breadcrumb . $title;
    }

    // Guard to only modify the main single-post content loop.
    private function should_enhance_single(): bool
    {
        return !is_admin() && is_single() && get_post_type() === 'post' && in_the_loop() && is_main_query();
    }

    // Ensures heading IDs exist so TOC links can navigate to sections.
    private function inject_heading_ids(string $content): array
    {
        $headings = [];
        $used_ids = [];

        $updated_content = preg_replace_callback(
            '/<h([23])([^>]*)>(.*?)<\/h\1>/is',
            function (array $matches) use (&$headings, &$used_ids): string {
                $level = (int) $matches[1];
                $attrs = $matches[2];
                $inner = $matches[3];
                $text = trim(wp_strip_all_tags($inner));

                if ($text === '') {
                    return $matches[0];
                }

                $existing_id = '';
                if (preg_match('/id\s*=\s*"([^"]+)"/i', $attrs, $id_match)) {
                    $existing_id = sanitize_title($id_match[1]);
                }

                $base_id = $existing_id !== '' ? $existing_id : sanitize_title($text);
                if ($base_id === '') {
                    $base_id = 'sekcia';
                }

                $candidate_id = $base_id;
                $suffix = 2;
                while (in_array($candidate_id, $used_ids, true)) {
                    $candidate_id = $base_id . '-' . $suffix;
                    $suffix++;
                }

                $used_ids[] = $candidate_id;

                if ($existing_id === '') {
                    $attrs .= ' id="' . esc_attr($candidate_id) . '"';
                }

                $headings[] = [
                    'id' => $candidate_id,
                    'text' => $text,
                    'level' => $level,
                ];

                return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
            },
            $content
        );

        if (!is_string($updated_content)) {
            return [$content, []];
        }

        return [$updated_content, $headings];
    }

    // Renders post featured image above article body if one is set.
    private function build_featured_image_html(): string
    {
        $post_id = get_the_ID();
        if (!$post_id || !has_post_thumbnail($post_id)) {
            return '';
        }

        return '<figure class="bde-featured-image">' . get_the_post_thumbnail(
            $post_id,
            'large',
            [
                'loading' => 'eager',
                'decoding' => 'async',
                'class' => 'bde-featured-image__img',
            ]
        ) . '</figure>';
    }

    // Builds breadcrumb path for single post pages.
    private function build_single_breadcrumb(): string
    {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $category_links = '';
        $categories = get_the_category($post_id);

        if (!empty($categories) && !is_wp_error($categories)) {
            $primary_category = $categories[0];
            $category_links = '<a href="' . esc_url(get_category_link($primary_category->term_id)) . '">' . esc_html($primary_category->name) . '</a>';
        }

        $parts = [
            '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Domov', 'blog-design-enhancer') . '</a>',
        ];

        if ($category_links !== '') {
            $parts[] = $category_links;
        }

        $parts[] = '<span>' . esc_html(get_the_title($post_id)) . '</span>';

        return '<nav class="bde-breadcrumb" aria-label="Breadcrumb">' . implode(' <span class="bde-breadcrumb-sep">/</span> ', $parts) . '</nav>';
    }

    // Builds compact table-of-contents module from collected headings.
    private function build_toc(array $headings): string
    {
        if (count($headings) < 2) {
            return '';
        }

        $items = '';

        foreach ($headings as $heading) {
            $class = $heading['level'] === 3 ? 'bde-toc-item bde-toc-item--h3' : 'bde-toc-item';
            $items .= '<li class="' . esc_attr($class) . '"><a href="#' . esc_attr($heading['id']) . '">' . esc_html($heading['text']) . '</a></li>';
        }

        return '<section class="bde-toc" aria-label="Obsah clanku"><h2 class="bde-module-title">Obsah clanku</h2><ol>' . $items . '</ol></section>';
    }

    // Appends modules placed below the article text.
    private function build_bottom_modules(): string
    {
        $share = $this->build_share_module();
        $related = $this->build_related_by_tags_module();

        return '<section class="bde-bottom-modules">' . $share . $related . '</section>';
    }

    // Generates social share links from current post URL and title.
    private function build_share_module(): string
    {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $permalink = rawurlencode(get_permalink($post_id));
        $title = rawurlencode(get_the_title($post_id));

        $links = [
            'facebook' => [
                'label' => 'Facebook',
                'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $permalink,
            ],
            'x' => [
                'label' => 'X',
                'url' => 'https://x.com/intent/tweet?url=' . $permalink . '&text=' . $title,
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $permalink,
            ],
        ];

        $items = '';
        foreach ($links as $network => $data) {
            $items .= '<a class="bde-share-link bde-share-link--' . esc_attr($network) . '" href="' . esc_url($data['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($data['label']) . '</a>';
        }

        return '<section class="bde-share"><h3 class="bde-module-title">Zdielat clanok</h3><div class="bde-share-links">' . $items . '</div></section>';
    }

    // Builds related topics and related posts by shared tags.
    private function build_related_by_tags_module(): string
    {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $tag_ids = wp_get_post_tags($post_id, ['fields' => 'ids']);

        if (empty($tag_ids)) {
            return '';
        }

        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => [$post_id],
            'ignore_sticky_posts' => true,
            'tag__in' => $tag_ids,
        ]);

        if (!$query->have_posts()) {
            return '';
        }

        $items = '';

        while ($query->have_posts()) {
            $query->the_post();
            $items .= '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
        }

        wp_reset_postdata();

        $tags_html = '';
        $tags = get_the_tags($post_id);
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $tags_html .= '<a class="bde-topic-tag" href="' . esc_url(get_tag_link($tag->term_id)) . '">#' . esc_html($tag->name) . '</a>';
            }
        }

        return '<section class="bde-related"><h3 class="bde-module-title">Related topics</h3><div class="bde-topic-tags">' . $tags_html . '</div><ul class="bde-related-list">' . $items . '</ul></section>';
    }
}
