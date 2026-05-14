<?php

if (!defined('ABSPATH')) {
    exit;
}

// Handles admin settings registration, sanitization, and rendering.
class BDE_Settings
{
    private const OPTION_KEY = 'bde_settings';

    // Register wp-admin hooks for settings UI.
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // Adds Settings > Blog Design Enhancer page.
    public function register_settings_page(): void
    {
        add_options_page(
            __('Blog Design Enhancer', 'blog-design-enhancer'),
            __('Blog Design Enhancer', 'blog-design-enhancer'),
            'manage_options',
            'blog-design-enhancer',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        // Persist all plugin options under one option array.
        register_setting(
            'bde_settings_group',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );

        // Main section keeps blog UX and custom code options together.
        add_settings_section(
            'bde_main_section',
            __('Blog and Article Enhancements', 'blog-design-enhancer'),
            '__return_false',
            'blog-design-enhancer'
        );

        add_settings_field(
            'excerpt_length',
            __('Excerpt Length (words)', 'blog-design-enhancer'),
            [$this, 'render_number_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'excerpt_length', 'min' => 10, 'max' => 80]
        );

        add_settings_field(
            'facebook_url',
            __('Facebook URL', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'facebook_url', 'placeholder' => 'https://facebook.com/yourpage']
        );

        add_settings_field(
            'x_url',
            __('X (Twitter) URL', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'x_url', 'placeholder' => 'https://x.com/yourprofile']
        );

        add_settings_field(
            'linkedin_url',
            __('LinkedIn URL', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'linkedin_url', 'placeholder' => 'https://linkedin.com/company/your-company']
        );

        add_settings_field(
            'cta_title',
            __('CTA Title', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'cta_title', 'placeholder' => 'Objavte svoje silne stranky']
        );

        add_settings_field(
            'cta_text',
            __('CTA Text', 'blog-design-enhancer'),
            [$this, 'render_textarea_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'cta_text', 'rows' => 4]
        );

        add_settings_field(
            'cta_button_text',
            __('CTA Button Text', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'cta_button_text', 'placeholder' => 'Ziskat poradenstvo']
        );

        add_settings_field(
            'cta_button_url',
            __('CTA Button URL', 'blog-design-enhancer'),
            [$this, 'render_text_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'cta_button_url', 'placeholder' => 'https://digitalnyradca.sk']
        );

        add_settings_field(
            'custom_css',
            __('Custom CSS', 'blog-design-enhancer'),
            [$this, 'render_textarea_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'custom_css', 'rows' => 10]
        );

        add_settings_field(
            'custom_js',
            __('Custom JS', 'blog-design-enhancer'),
            [$this, 'render_textarea_field'],
            'blog-design-enhancer',
            'bde_main_section',
            ['field' => 'custom_js', 'rows' => 10]
        );
    }

    public function sanitize_settings(array $input): array
    {
        // Normalize and sanitize every option field before save.
        $defaults = $this->get_default_settings();

        return [
            'excerpt_length' => isset($input['excerpt_length']) ? max(10, min(80, absint($input['excerpt_length']))) : $defaults['excerpt_length'],
            'facebook_url' => !empty($input['facebook_url']) ? esc_url_raw($input['facebook_url']) : '',
            'x_url' => !empty($input['x_url']) ? esc_url_raw($input['x_url']) : '',
            'linkedin_url' => !empty($input['linkedin_url']) ? esc_url_raw($input['linkedin_url']) : '',
            'cta_title' => isset($input['cta_title']) ? sanitize_text_field($input['cta_title']) : $defaults['cta_title'],
            'cta_text' => isset($input['cta_text']) ? sanitize_textarea_field($input['cta_text']) : $defaults['cta_text'],
            'cta_button_text' => isset($input['cta_button_text']) ? sanitize_text_field($input['cta_button_text']) : $defaults['cta_button_text'],
            'cta_button_url' => !empty($input['cta_button_url']) ? esc_url_raw($input['cta_button_url']) : $defaults['cta_button_url'],
            'custom_css' => isset($input['custom_css']) ? wp_unslash($input['custom_css']) : '',
            'custom_js' => isset($input['custom_js']) ? wp_unslash($input['custom_js']) : '',
        ];
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Blog Design Enhancer', 'blog-design-enhancer') . '</h1>';
        echo '<p>' . esc_html__('Optimize article UX and conversion: CTA modules, table of contents, breadcrumbs, related topics, and styling.', 'blog-design-enhancer') . '</p>';
        echo '<form method="post" action="options.php">';

        // WordPress settings API form renderer.
        settings_fields('bde_settings_group');
        do_settings_sections('blog-design-enhancer');
        submit_button();

        echo '</form>';
        echo '</div>';
    }

    // Shared number field renderer.
    public function render_number_field(array $args): void
    {
        $settings = $this->get_settings();
        $field = $args['field'];
        $min = isset($args['min']) ? (int) $args['min'] : 0;
        $max = isset($args['max']) ? (int) $args['max'] : 100;
        $value = isset($settings[$field]) ? (int) $settings[$field] : 0;

        echo '<input type="number" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '" class="small-text" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($field) . ']" value="' . esc_attr((string) $value) . '" />';
    }

    // Shared single-line text field renderer.
    public function render_text_field(array $args): void
    {
        $settings = $this->get_settings();
        $field = $args['field'];
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';

        echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" />';
    }

    // Shared textarea renderer for larger values.
    public function render_textarea_field(array $args): void
    {
        $settings = $this->get_settings();
        $field = $args['field'];
        $rows = isset($args['rows']) ? (int) $args['rows'] : 8;
        $value = isset($settings[$field]) ? $settings[$field] : '';

        echo '<textarea class="large-text code" rows="' . esc_attr((string) $rows) . '" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($field) . ']">' . esc_textarea($value) . '</textarea>';
    }

    // Public settings accessor with defaults merged in.
    public function get_settings(): array
    {
        $settings = get_option(self::OPTION_KEY, []);
        return wp_parse_args($settings, $this->get_default_settings());
    }

    // Default values used for first run and missing keys.
    private function get_default_settings(): array
    {
        return [
            'excerpt_length' => 24,
            'facebook_url' => '',
            'x_url' => '',
            'linkedin_url' => '',
            'cta_title' => 'Objavte svoje silne stranky',
            'cta_text' => 'Spravte si testy a ziskajte odporucania, ako sa presadit v praci v dobe AI.',
            'cta_button_text' => 'Ziskat poradenstvo',
            'cta_button_url' => 'https://digitalnyradca.sk',
            'custom_css' => '',
            'custom_js' => '',
        ];
    }
}
