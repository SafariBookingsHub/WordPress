<?php
    /**
     * Register the block patterns and block patterns categories
     *
     * @package WordPress
     * @since   5.5.0
     */

    add_theme_support('core-block-patterns');

    /**
     * Registers the core block patterns and categories.
     *
     * @since  5.5.0
     * @since  6.3.0 Added source to core block patterns.
     * @access private
     */
    function _register_core_block_patterns_and_categories()
    {
        $should_register_core_patterns = get_theme_support('core-block-patterns');

        if($should_register_core_patterns)
        {
            $core_block_patterns = [
                'query-standard-posts',
                'query-medium-posts',
                'query-small-posts',
                'query-grid-posts',
                'query-large-title-posts',
                'query-offset-posts',
                'social-links-shared-background-color',
            ];

            foreach($core_block_patterns as $core_block_pattern)
            {
                $pattern = require __DIR__.'/block-patterns/'.$core_block_pattern.'.php';
                $pattern['source'] = 'core';
                register_block_pattern('core/'.$core_block_pattern, $pattern);
            }
        }

        register_block_pattern_category('banner', ['label' => _x('Banners', 'Block pattern category')]);
        register_block_pattern_category('buttons', [
            'label' => _x('Buttons', 'Block pattern category'),
            'description' => __('Patterns that contain buttons and call to actions.'),
        ]);
        register_block_pattern_category('columns', [
            'label' => _x('Columns', 'Block pattern category'),
            'description' => __('Multi-column patterns with more complex layouts.'),
        ]);
        register_block_pattern_category('text', [
            'label' => _x('Text', 'Block pattern category'),
            'description' => __('Patterns containing mostly text.'),
        ]);
        register_block_pattern_category('query', [
            'label' => _x('Posts', 'Block pattern category'),
            'description' => __('Display your latest posts in lists, grids or other layouts.'),
        ]);
        register_block_pattern_category('featured', [
            'label' => _x('Featured', 'Block pattern category'),
            'description' => __('A set of high quality curated patterns.'),
        ]);
        register_block_pattern_category('call-to-action', [
            'label' => _x('Call to Action', 'Block pattern category'),
            'description' => __('Sections whose purpose is to trigger a specific action.'),
        ]);
        register_block_pattern_category('team', [
            'label' => _x('Team', 'Block pattern category'),
            'description' => __('A variety of designs to display your team members.'),
        ]);
        register_block_pattern_category('testimonials', [
            'label' => _x('Testimonials', 'Block pattern category'),
            'description' => __('Share reviews and feedback about your brand/business.'),
        ]);
        register_block_pattern_category('services', [
            'label' => _x('Services', 'Block pattern category'),
            'description' => __('Briefly describe what your business does and how you can help.'),
        ]);
        register_block_pattern_category('contact', [
            'label' => _x('Contact', 'Block pattern category'),
            'description' => __('Display your contact information.'),
        ]);
        register_block_pattern_category('about', [
            'label' => _x('About', 'Block pattern category'),
            'description' => __('Introduce yourself.'),
        ]);
        register_block_pattern_category('portfolio', [
            'label' => _x('Portfolio', 'Block pattern category'),
            'description' => __('Showcase your latest work.'),
        ]);
        register_block_pattern_category('gallery', [
            'label' => _x('Gallery', 'Block pattern category'),
            'description' => __('Different layouts for displaying images.'),
        ]);
        register_block_pattern_category('media', [
            'label' => _x('Media', 'Block pattern category'),
            'description' => __('Different layouts containing video or audio.'),
        ]);
        register_block_pattern_category('posts', [
            'label' => _x('Posts', 'Block pattern category'),
            'description' => __('Display your latest posts in lists, grids or other layouts.'),
        ]);
        register_block_pattern_category('footer', [
            'label' => _x('Footers', 'Block pattern category'),
            'description' => __('A variety of footer designs displaying information and site navigation.'),
        ]);
        register_block_pattern_category('header', [
            'label' => _x('Headers', 'Block pattern category'),
            'description' => __('A variety of header designs displaying your site title and navigation.'),
        ]);
    }

    /**
     * Normalize the pattern properties to camelCase.
     *
     * The API's format is snake_case, `register_block_pattern()` expects camelCase.
     *
     * @param array $pattern Pattern as returned from the Pattern Directory API.
     *
     * @return array Normalized pattern.
     * @since  6.2.0
     * @access private
     *
     */
    function wp_normalize_remote_block_pattern($pattern)
    {
        if(isset($pattern['block_types']))
        {
            $pattern['blockTypes'] = $pattern['block_types'];
            unset($pattern['block_types']);
        }

        if(isset($pattern['viewport_width']))
        {
            $pattern['viewportWidth'] = $pattern['viewport_width'];
            unset($pattern['viewport_width']);
        }

        return (array) $pattern;
    }

    /**
     * Register Core's official patterns from wordpress.org/patterns.
     *
     * @param WP_Screen $deprecated Unused. Formerly the screen that the current request was triggered from.
     *
     * @since 5.9.0 The $current_screen argument was removed.
     * @since 6.2.0 Normalize the pattern from the API (snake_case) to the
     *              format expected by `register_block_pattern` (camelCase).
     * @since 6.3.0 Add 'pattern-directory/core' to the pattern's 'source'.
     *
     * @since 5.8.0
     */
    function _load_remote_block_patterns($deprecated = null)
    {
        if(! empty($deprecated))
        {
            _deprecated_argument(__FUNCTION__, '5.9.0');
            $current_screen = $deprecated;
            if(! $current_screen->is_block_editor)
            {
                return;
            }
        }

        $supports_core_patterns = get_theme_support('core-block-patterns');

        /**
         * Filter to disable remote block patterns.
         *
         * @param bool $should_load_remote
         *
         * @since 5.8.0
         *
         */
        $should_load_remote = apply_filters('should_load_remote_block_patterns', true);

        if($supports_core_patterns && $should_load_remote)
        {
            $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
            $core_keyword_id = 11; // 11 is the ID for "core".
            $request->set_param('keyword', $core_keyword_id);
            $response = rest_do_request($request);
            if($response->is_error())
            {
                return;
            }
            $patterns = $response->get_data();

            foreach($patterns as $pattern)
            {
                $pattern['source'] = 'pattern-directory/core';
                $normalized_pattern = wp_normalize_remote_block_pattern($pattern);
                $pattern_name = 'core/'.sanitize_title($normalized_pattern['title']);
                register_block_pattern($pattern_name, $normalized_pattern);
            }
        }
    }

    /**
     * Register `Featured` (category) patterns from wordpress.org/patterns.
     *
     * @since 5.9.0
     * @since 6.2.0 Normalized the pattern from the API (snake_case) to the
     *              format expected by `register_block_pattern()` (camelCase).
     * @since 6.3.0 Add 'pattern-directory/featured' to the pattern's 'source'.
     */
    function _load_remote_featured_patterns()
    {
        $supports_core_patterns = get_theme_support('core-block-patterns');

        /** This filter is documented in wp-includes/block-patterns.php */
        $should_load_remote = apply_filters('should_load_remote_block_patterns', true);

        if(! $should_load_remote || ! $supports_core_patterns)
        {
            return;
        }

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $featured_cat_id = 26; // This is the `Featured` category id from pattern directory.
        $request->set_param('category', $featured_cat_id);
        $response = rest_do_request($request);
        if($response->is_error())
        {
            return;
        }
        $patterns = $response->get_data();
        $registry = WP_Block_Patterns_Registry::get_instance();
        foreach($patterns as $pattern)
        {
            $pattern['source'] = 'pattern-directory/featured';
            $normalized_pattern = wp_normalize_remote_block_pattern($pattern);
            $pattern_name = sanitize_title($normalized_pattern['title']);
            // Some patterns might be already registered as core patterns with the `core` prefix.
            $is_registered = $registry->is_registered($pattern_name) || $registry->is_registered("core/$pattern_name");
            if(! $is_registered)
            {
                register_block_pattern($pattern_name, $normalized_pattern);
            }
        }
    }

    /**
     * Registers patterns from Pattern Directory provided by a theme's
     * `theme.json` file.
     *
     * @since  6.0.0
     * @since  6.2.0 Normalized the pattern from the API (snake_case) to the
     *              format expected by `register_block_pattern()` (camelCase).
     * @since  6.3.0 Add 'pattern-directory/theme' to the pattern's 'source'.
     * @access private
     */
    function _register_remote_theme_patterns()
    {
        /** This filter is documented in wp-includes/block-patterns.php */
        if(! apply_filters('should_load_remote_block_patterns', true))
        {
            return;
        }

        if(! wp_theme_has_theme_json())
        {
            return;
        }

        $pattern_settings = wp_get_theme_directory_pattern_slugs();
        if(empty($pattern_settings))
        {
            return;
        }

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request['slug'] = $pattern_settings;
        $response = rest_do_request($request);
        if($response->is_error())
        {
            return;
        }
        $patterns = $response->get_data();
        $patterns_registry = WP_Block_Patterns_Registry::get_instance();
        foreach($patterns as $pattern)
        {
            $pattern['source'] = 'pattern-directory/theme';
            $normalized_pattern = wp_normalize_remote_block_pattern($pattern);
            $pattern_name = sanitize_title($normalized_pattern['title']);
            // Some patterns might be already registered as core patterns with the `core` prefix.
            $is_registered = $patterns_registry->is_registered($pattern_name) || $patterns_registry->is_registered("core/$pattern_name");
            if(! $is_registered)
            {
                register_block_pattern($pattern_name, $normalized_pattern);
            }
        }
    }

    /**
     * Register any patterns that the active theme may provide under its
     * `./patterns/` directory. Each pattern is defined as a PHP file and defines
     * its metadata using plugin-style headers. The minimum required definition is:
     *
     *     /**
     *      * Title: My Pattern
     *      * Slug: my-theme/my-pattern
     *      *
     *
     * The output of the PHP source corresponds to the content of the pattern, e.g.:
     *
     *     <main><p><?php echo "Hello"; ?></p></main>
     *
     * If applicable, this will collect from both parent and child theme.
     *
     * Other settable fields include:
     *
     *   - Description
     *   - Viewport Width
     *   - Inserter         (yes/no)
     *   - Categories       (comma-separated values)
     *   - Keywords         (comma-separated values)
     *   - Block Types      (comma-separated values)
     *   - Post Types       (comma-separated values)
     *   - Template Types   (comma-separated values)
     *
     * @since  6.0.0
     * @since  6.1.0 The `postTypes` property was added.
     * @since  6.2.0 The `templateTypes` property was added.
     * @access private
     */
    function _register_theme_block_patterns()
    {
        $default_headers = [
            'title' => 'Title',
            'slug' => 'Slug',
            'description' => 'Description',
            'viewportWidth' => 'Viewport Width',
            'inserter' => 'Inserter',
            'categories' => 'Categories',
            'keywords' => 'Keywords',
            'blockTypes' => 'Block Types',
            'postTypes' => 'Post Types',
            'templateTypes' => 'Template Types',
        ];

        /*
         * Register patterns for the active theme. If the theme is a child theme,
         * let it override any patterns from the parent theme that shares the same slug.
         */
        $themes = [];
        $stylesheet = get_stylesheet();
        $template = get_template();
        if($stylesheet !== $template)
        {
            $themes[] = wp_get_theme($stylesheet);
        }
        $themes[] = wp_get_theme($template);

        foreach($themes as $theme)
        {
            $dirpath = $theme->get_stylesheet_directory().'/patterns/';
            if(! is_dir($dirpath) || ! is_readable($dirpath))
            {
                continue;
            }
            if(file_exists($dirpath))
            {
                $files = glob($dirpath.'*.php');
                if($files)
                {
                    foreach($files as $file)
                    {
                        $pattern_data = get_file_data($file, $default_headers);

                        if(empty($pattern_data['slug']))
                        {
                            _doing_it_wrong('_register_theme_block_patterns', sprintf(/* translators: %s: file name. */ __('Could not register file "%s" as a block pattern ("Slug" field missing)'), $file), '6.0.0');
                            continue;
                        }

                        if(! preg_match('/^[A-z0-9\/_-]+$/', $pattern_data['slug']))
                        {
                            _doing_it_wrong('_register_theme_block_patterns', sprintf(/* translators: %1s: file name; %2s: slug value found. */ __('Could not register file "%1$s" as a block pattern (invalid slug "%2$s")'), $file, $pattern_data['slug']), '6.0.0');
                        }

                        if(WP_Block_Patterns_Registry::get_instance()->is_registered($pattern_data['slug']))
                        {
                            continue;
                        }

                        // Title is a required property.
                        if(! $pattern_data['title'])
                        {
                            _doing_it_wrong('_register_theme_block_patterns', sprintf(/* translators: %1s: file name; %2s: slug value found. */ __('Could not register file "%s" as a block pattern ("Title" field missing)'), $file), '6.0.0');
                            continue;
                        }

                        // For properties of type array, parse data as comma-separated.
                        foreach(['categories', 'keywords', 'blockTypes', 'postTypes', 'templateTypes'] as $property)
                        {
                            if(! empty($pattern_data[$property]))
                            {
                                $pattern_data[$property] = array_filter(preg_split('/[\s,]+/', (string) $pattern_data[$property]));
                            }
                            else
                            {
                                unset($pattern_data[$property]);
                            }
                        }

                        // Parse properties of type int.
                        foreach(['viewportWidth'] as $property)
                        {
                            if(! empty($pattern_data[$property]))
                            {
                                $pattern_data[$property] = (int) $pattern_data[$property];
                            }
                            else
                            {
                                unset($pattern_data[$property]);
                            }
                        }

                        // Parse properties of type bool.
                        foreach(['inserter'] as $property)
                        {
                            if(! empty($pattern_data[$property]))
                            {
                                $pattern_data[$property] = in_array(strtolower($pattern_data[$property]), [
                                    'yes',
                                    'true'
                                ],                                  true);
                            }
                            else
                            {
                                unset($pattern_data[$property]);
                            }
                        }

                        // Translate the pattern metadata.
                        $text_domain = $theme->get('TextDomain');
                        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.LowLevelTranslationFunction
                        $pattern_data['title'] = translate_with_gettext_context($pattern_data['title'], 'Pattern title', $text_domain);
                        if(! empty($pattern_data['description']))
                        {
                            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.LowLevelTranslationFunction
                            $pattern_data['description'] = translate_with_gettext_context($pattern_data['description'], 'Pattern description', $text_domain);
                        }

                        // The actual pattern content is the output of the file.
                        ob_start();
                        include $file;
                        $pattern_data['content'] = ob_get_clean();
                        if(! $pattern_data['content'])
                        {
                            continue;
                        }

                        register_block_pattern($pattern_data['slug'], $pattern_data);
                    }
                }
            }
        }
    }

    add_action('init', '_register_theme_block_patterns');
