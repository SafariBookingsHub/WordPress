<?php
    /**
     * Twenty Twelve functions and definitions
     *
     * Sets up the theme and provides some helper functions, which are used
     * in the theme as custom template tags. Others are attached to action and
     * filter hooks in WordPress to change core functionality.
     *
     * When using a child theme you can override certain functions (those wrapped
     * in a function_exists() call) by defining them first in your child theme's
     * functions.php file. The child theme's functions.php file is included before
     * the parent theme's file, so the child theme functions would be used.
     *
     * @link       https://developer.wordpress.org/themes/basics/theme-functions/
     * @link       https://developer.wordpress.org/themes/advanced-topics/child-themes/
     *
     * Functions that are not pluggable (not wrapped in function_exists()) are instead attached
     * to a filter or action hook.
     *
     * For more information on hooks, actions, and filters, @link https://developer.wordpress.org/plugins/
     *
     * @package    WordPress
     * @subpackage Twenty_Twelve
     * @since      Twenty Twelve 1.0
     */

// Set up the content width value based on the theme's design and stylesheet.
    if(! isset($content_width))
    {
        $content_width = 625;
    }

    /**
     * Twenty Twelve setup.
     *
     * Sets up theme defaults and registers the various WordPress features that
     * Twenty Twelve supports.
     *
     * @uses  load_theme_textdomain() For translation/localization support.
     * @uses  add_editor_style() To add a Visual Editor stylesheet.
     * @uses  add_theme_support() To add support for post thumbnails, automatic feed links,
     *  custom background, and post formats.
     * @uses  register_nav_menu() To add support for navigation menus.
     * @uses  set_post_thumbnail_size() To set a custom post thumbnail size.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_setup()
    {
        /*
         * Makes Twenty Twelve available for translation.
         *
         * Translations can be filed at WordPress.org. See: https://translate.wordpress.org/projects/wp-themes/twentytwelve
         * If you're building a theme based on Twenty Twelve, use a find and replace
         * to change 'twentytwelve' to the name of your theme in all the template files.
         *
         * Manual loading of text domain is not required after the introduction of
         * just in time translation loading in WordPress version 4.6.
         *
         * @ticket 58318
         */
        if(version_compare($GLOBALS['wp_version'], '4.6', '<'))
        {
            load_theme_textdomain('twentytwelve');
        }

        // This theme styles the visual editor with editor-style.css to match the theme style.
        add_editor_style();

        // Load regular editor styles into the new block-based editor.
        add_theme_support('editor-styles');

        // Load default block styles.
        add_theme_support('wp-block-styles');

        // Add support for responsive embeds.
        add_theme_support('responsive-embeds');

        // Add support for custom color scheme.
        add_theme_support('editor-color-palette', [
            [
                'name' => __('Blue', 'twentytwelve'),
                'slug' => 'blue',
                'color' => '#21759b',
            ],
            [
                'name' => __('Dark Gray', 'twentytwelve'),
                'slug' => 'dark-gray',
                'color' => '#444',
            ],
            [
                'name' => __('Medium Gray', 'twentytwelve'),
                'slug' => 'medium-gray',
                'color' => '#9f9f9f',
            ],
            [
                'name' => __('Light Gray', 'twentytwelve'),
                'slug' => 'light-gray',
                'color' => '#e6e6e6',
            ],
            [
                'name' => __('White', 'twentytwelve'),
                'slug' => 'white',
                'color' => '#fff',
            ],
        ]);

        // Adds RSS feed links to <head> for posts and comments.
        add_theme_support('automatic-feed-links');

        // This theme supports a variety of post formats.
        add_theme_support('post-formats', ['aside', 'image', 'link', 'quote', 'status']);

        // This theme uses wp_nav_menu() in one location.
        register_nav_menu('primary', __('Primary Menu', 'twentytwelve'));

        /*
         * This theme supports custom background color and image,
         * and here we also set up the default background color.
         */
        add_theme_support('custom-background', [
            'default-color' => 'e6e6e6',
        ]);

        // This theme uses a custom image size for featured images, displayed on "standard" posts.
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(624, 9999); // Unlimited height, soft crop.

        // Indicate widget sidebars can use selective refresh in the Customizer.
        add_theme_support('customize-selective-refresh-widgets');
    }

    add_action('after_setup_theme', 'twentytwelve_setup');

    /**
     * Add support for a custom header image.
     */
    require get_template_directory().'/inc/custom-header.php';

    /**
     * Add block patterns.
     */
    require get_template_directory().'/inc/block-patterns.php';

    if(! function_exists('twentytwelve_get_font_url')) :
        /**
         * Return the font stylesheet URL if available.
         *
         * The use of Open Sans by default is localized. For languages that use
         * characters not supported by the font, the font can be disabled.
         *
         * @return string Font stylesheet or empty string if disabled.
         * @since Twenty Twelve 3.9 Replaced Google URL with self-hosted font.
         *
         * @since Twenty Twelve 1.2
         */
        function twentytwelve_get_font_url()
        {
            $font_url = '';

            /*
            * translators: If there are characters in your language that are not supported
            * by Open Sans, translate this to 'off'. Do not translate into your own language.
            */
            if('off' !== _x('on', 'Open Sans font: on or off', 'twentytwelve'))
            {
                $font_url = get_template_directory_uri().'/fonts/font-open-sans.css';
            }

            return $font_url;
        }
    endif;

    /**
     * Enqueue scripts and styles for front end.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_scripts_styles()
    {
        global $wp_styles;

        /*
         * Adds JavaScript to pages with the comment form to support
         * sites with threaded comments (when in use).
         */
        if(is_singular() && comments_open() && get_option('thread_comments'))
        {
            wp_enqueue_script('comment-reply');
        }

        // Adds JavaScript for handling the navigation menu hide-and-show behavior.
        wp_enqueue_script('twentytwelve-navigation', get_template_directory_uri().'/js/navigation.js', ['jquery'], '20141205', [
            'in_footer' => false, // Because involves header.
            'strategy' => 'defer',
        ]);

        $font_url = twentytwelve_get_font_url();
        if(! empty($font_url))
        {
            $font_version = (0 === strpos((string) twentytwelve_get_font_url(), get_template_directory_uri().'/')) ? '20230328' : null;
            wp_enqueue_style('twentytwelve-fonts', esc_url_raw($font_url), [], $font_version);
        }

        // Loads our main stylesheet.
        wp_enqueue_style('twentytwelve-style', get_stylesheet_uri(), [], '20230808');

        // Theme block stylesheet.
        wp_enqueue_style('twentytwelve-block-style', get_template_directory_uri().'/css/blocks.css', ['twentytwelve-style'], '20230213');

        // Loads the Internet Explorer specific stylesheet.
        wp_enqueue_style('twentytwelve-ie', get_template_directory_uri().'/css/ie.css', ['twentytwelve-style'], '20150214');
        $wp_styles->add_data('twentytwelve-ie', 'conditional', 'lt IE 9');
    }

    add_action('wp_enqueue_scripts', 'twentytwelve_scripts_styles');

    /**
     * Enqueue styles for the block-based editor.
     *
     * @since Twenty Twelve 2.6
     */
    function twentytwelve_block_editor_styles()
    {
        // Block styles.
        wp_enqueue_style('twentytwelve-block-editor-style', get_template_directory_uri().'/css/editor-blocks.css', [], '20230213');
        // Add custom fonts.
        $font_version = (0 === strpos((string) twentytwelve_get_font_url(), get_template_directory_uri().'/')) ? '20230328' : null;
        wp_enqueue_style('twentytwelve-fonts', twentytwelve_get_font_url(), [], $font_version);
    }

    add_action('enqueue_block_editor_assets', 'twentytwelve_block_editor_styles');

    /**
     * Add preconnect for Google Fonts.
     *
     * @param array  $urls          URLs to print for resource hints.
     * @param string $relation_type The relation type the URLs are printed.
     *
     * @return array URLs to print for resource hints.
     * @deprecated Twenty Twelve 3.9 Disabled filter because, by default, fonts are self-hosted.
     *
     * @since      Twenty Twelve 2.2
     */
    function twentytwelve_resource_hints($urls, $relation_type)
    {
        if(wp_style_is('twentytwelve-fonts', 'queue') && 'preconnect' === $relation_type)
        {
            if(version_compare($GLOBALS['wp_version'], '4.7-alpha', '>='))
            {
                $urls[] = [
                    'href' => 'https://fonts.gstatic.com',
                    'crossorigin',
                ];
            }
            else
            {
                $urls[] = 'https://fonts.gstatic.com';
            }
        }

        return $urls;
    }

// add_filter( 'wp_resource_hints', 'twentytwelve_resource_hints', 10, 2 );

    /**
     * Filter TinyMCE CSS path to include hosted fonts.
     *
     * Adds additional stylesheets to the TinyMCE editor if needed.
     *
     * @param string $mce_css CSS path to load in TinyMCE.
     *
     * @return string Filtered CSS path.
     * @uses  twentytwelve_get_font_url() To get the font stylesheet URL.
     *
     * @since Twenty Twelve 1.2
     *
     */
    function twentytwelve_mce_css($mce_css)
    {
        $font_url = twentytwelve_get_font_url();

        if(empty($font_url))
        {
            return $mce_css;
        }

        if(! empty($mce_css))
        {
            $mce_css .= ',';
        }

        $mce_css .= esc_url_raw(str_replace(',', '%2C', $font_url));

        return $mce_css;
    }

    add_filter('mce_css', 'twentytwelve_mce_css');

    /**
     * Filter the page title.
     *
     * Creates a nicely formatted and more specific title element text
     * for output in head of document, based on current view.
     *
     * @param string $title Default title text for current view.
     * @param string $sep   Optional separator.
     *
     * @return string Filtered title.
     * @since Twenty Twelve 1.0
     *
     */
    function twentytwelve_wp_title($title, $sep)
    {
        global $paged, $page;

        if(is_feed())
        {
            return $title;
        }

        // Add the site name.
        $title .= get_bloginfo('name', 'display');

        // Add the site description for the home/front page.
        $site_description = get_bloginfo('description', 'display');
        if($site_description && (is_home() || is_front_page()))
        {
            $title = "$title $sep $site_description";
        }

        // Add a page number if necessary.
        if(($paged >= 2 || $page >= 2) && ! is_404())
        {
            /* translators: %s: Page number. */
            $title = "$title $sep ".sprintf(__('Page %s', 'twentytwelve'), max($paged, $page));
        }

        return $title;
    }

    add_filter('wp_title', 'twentytwelve_wp_title', 10, 2);

    /**
     * Filter the page menu arguments.
     *
     * Makes our wp_nav_menu() fallback -- wp_page_menu() -- show a home link.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_page_menu_args($args)
    {
        if(! isset($args['show_home']))
        {
            $args['show_home'] = true;
        }

        return $args;
    }

    add_filter('wp_page_menu_args', 'twentytwelve_page_menu_args');

    /**
     * Register sidebars.
     *
     * Registers our main widget area and the front page widget areas.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_widgets_init()
    {
        register_sidebar([
                             'name' => __('Main Sidebar', 'twentytwelve'),
                             'id' => 'sidebar-1',
                             'description' => __('Appears on posts and pages except the optional Front Page template, which has its own widgets', 'twentytwelve'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('First Front Page Widget Area', 'twentytwelve'),
                             'id' => 'sidebar-2',
                             'description' => __('Appears when using the optional Front Page template with a page set as Static Front Page', 'twentytwelve'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);

        register_sidebar([
                             'name' => __('Second Front Page Widget Area', 'twentytwelve'),
                             'id' => 'sidebar-3',
                             'description' => __('Appears when using the optional Front Page template with a page set as Static Front Page', 'twentytwelve'),
                             'before_widget' => '<aside id="%1$s" class="widget %2$s">',
                             'after_widget' => '</aside>',
                             'before_title' => '<h3 class="widget-title">',
                             'after_title' => '</h3>',
                         ]);
    }

    add_action('widgets_init', 'twentytwelve_widgets_init');

    if(! function_exists('wp_get_list_item_separator')) :
        /**
         * Retrieves the list item separator based on the locale.
         *
         * Added for backward compatibility to support pre-6.0.0 WordPress versions.
         *
         * @since 6.0.0
         */
        function wp_get_list_item_separator()
        {
            /* translators: Used between list items, there is a space after the comma. */
            return __(', ', 'twentytwelve');
        }
    endif;

    if(! function_exists('twentytwelve_content_nav')) :
        /**
         * Displays navigation to next/previous pages when applicable.
         *
         * @since Twenty Twelve 1.0
         */
        function twentytwelve_content_nav($html_id)
        {
            global $wp_query;

            if($wp_query->max_num_pages > 1) : ?>
                <nav id="<?php echo esc_attr($html_id); ?>" class="navigation">
                    <h3 class="assistive-text"><?php _e('Post navigation', 'twentytwelve'); ?></h3>
                    <div class="nav-previous"><?php next_posts_link(__('<span class="meta-nav">&larr;</span> Older posts', 'twentytwelve')); ?></div>
                    <div class="nav-next"><?php previous_posts_link(__('Newer posts <span class="meta-nav">&rarr;</span>', 'twentytwelve')); ?></div>
                </nav><!-- .navigation -->
            <?php
            endif;
        }
    endif;

    if(! function_exists('twentytwelve_comment')) :
        /**
         * Template for comments and pingbacks.
         *
         * To override this walker in a child theme without modifying the comments template
         * simply create your own twentytwelve_comment(), and that function will be used instead.
         *
         * Used as a callback by wp_list_comments() for displaying the comments.
         *
         * @since Twenty Twelve 1.0
         *
         * @global WP_Post $post Global post object.
         */
        function twentytwelve_comment($comment, $args, $depth)
        {
            $GLOBALS['comment'] = $comment;
            switch($comment->comment_type) :
                case 'pingback':
                case 'trackback':
                    // Display trackbacks differently than normal comments.
                    ?>
                    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
                    <p><?php _e('Pingback:', 'twentytwelve'); ?><?php comment_author_link(); ?><?php edit_comment_link(__('(Edit)', 'twentytwelve'), '<span class="edit-link">', '</span>'); ?></p>
                    <?php
                    break;
                default:
                    // Proceed with normal comments.
                    global $post;
                    ?>
                <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
                    <article id="comment-<?php comment_ID(); ?>" class="comment">
                        <header class="comment-meta comment-author vcard">
                            <?php
                                echo get_avatar($comment, 44);
                                printf(
                                    '<cite><b class="fn">%1$s</b> %2$s</cite>', get_comment_author_link(), // If current post author is also comment author, make it known visually.
                                    ($comment->user_id === $post->post_author) ? '<span>'.__('Post author', 'twentytwelve').'</span>' : ''
                                );
                                printf('<a href="%1$s"><time datetime="%2$s">%3$s</time></a>', esc_url(get_comment_link($comment->comment_ID)), get_comment_time('c'), /* translators: 1: Date, 2: Time. */ sprintf(__('%1$s at %2$s', 'twentytwelve'), get_comment_date(), get_comment_time()));
                            ?>
                        </header><!-- .comment-meta -->

                        <?php
                            $commenter = wp_get_current_commenter();
                            if($commenter['comment_author_email'])
                            {
                                $moderation_note = __('Your comment is awaiting moderation.', 'twentytwelve');
                            }
                            else
                            {
                                $moderation_note = __('Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'twentytwelve');
                            }
                        ?>

                        <?php if('0' === $comment->comment_approved) : ?>
                            <p class="comment-awaiting-moderation"><?php echo $moderation_note; ?></p>
                        <?php endif; ?>

                        <section class="comment-content comment">
                            <?php comment_text(); ?>
                            <?php edit_comment_link(__('Edit', 'twentytwelve'), '<p class="edit-link">', '</p>'); ?>
                        </section><!-- .comment-content -->

                        <div class="reply">
                            <?php
                                comment_reply_link(
                                    array_merge($args, [
                                        'reply_text' => __('Reply', 'twentytwelve'),
                                        'after' => ' <span>&darr;</span>',
                                        'depth' => $depth,
                                        'max_depth' => $args['max_depth'],
                                    ])
                                );
                            ?>
                        </div><!-- .reply -->
                    </article><!-- #comment-## -->
                    <?php
                    break;
            endswitch; // End comment_type check.
        }
    endif;

    if(! function_exists('twentytwelve_entry_meta')) :
        /**
         * Set up post entry meta.
         *
         * Prints HTML with meta information for current post: categories, tags, permalink, author, and date.
         *
         * Create your own twentytwelve_entry_meta() to override in a child theme.
         *
         * @since Twenty Twelve 1.0
         */
        function twentytwelve_entry_meta()
        {
            $categories_list = get_the_category_list(wp_get_list_item_separator());

            $tags_list = get_the_tag_list('', wp_get_list_item_separator());

            $date = sprintf('<a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a>', esc_url(get_permalink()), esc_attr(get_the_time()), esc_attr(get_the_date('c')), esc_html(get_the_date()));

            $author = sprintf('<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a></span>', esc_url(get_author_posts_url(get_the_author_meta('ID'))), /* translators: %s: Author display name. */ esc_attr(sprintf(__('View all posts by %s', 'twentytwelve'), get_the_author())), get_the_author());

            if($tags_list && ! is_wp_error($tags_list))
            {
                /* translators: 1: Category name, 2: Tag name, 3: Date, 4: Author display name. */
                $utility_text = __('This entry was posted in %1$s and tagged %2$s on %3$s<span class="by-author"> by %4$s</span>.', 'twentytwelve');
            }
            elseif($categories_list)
            {
                /* translators: 1: Category name, 3: Date, 4: Author display name. */
                $utility_text = __('This entry was posted in %1$s on %3$s<span class="by-author"> by %4$s</span>.', 'twentytwelve');
            }
            else
            {
                /* translators: 3: Date, 4: Author display name. */
                $utility_text = __('This entry was posted on %3$s<span class="by-author"> by %4$s</span>.', 'twentytwelve');
            }

            printf($utility_text, $categories_list, $tags_list, $date, $author);
        }
    endif;

    /**
     * Extend the default WordPress body classes.
     *
     * Extends the default WordPress body class to denote:
     * 1. Using a full-width layout, when no active widgets in the sidebar
     *    or full-width template.
     * 2. Front Page template: thumbnail in use and number of sidebars for
     *    widget areas.
     * 3. White or empty background color to change the layout and spacing.
     * 4. Custom fonts enabled.
     * 5. Single or multiple authors.
     *
     * @param array $classes Existing class values.
     *
     * @return array Filtered class values.
     * @since Twenty Twelve 1.0
     *
     */
    function twentytwelve_body_class($classes)
    {
        $background_color = get_background_color();
        $background_image = get_background_image();

        if(! is_active_sidebar('sidebar-1') || is_page_template('page-templates/full-width.php'))
        {
            $classes[] = 'full-width';
        }

        if(is_page_template('page-templates/front-page.php'))
        {
            $classes[] = 'template-front-page';
            if(has_post_thumbnail())
            {
                $classes[] = 'has-post-thumbnail';
            }
            if(is_active_sidebar('sidebar-2') && is_active_sidebar('sidebar-3'))
            {
                $classes[] = 'two-sidebars';
            }
        }

        if(empty($background_image))
        {
            if(empty($background_color))
            {
                $classes[] = 'custom-background-empty';
            }
            elseif(in_array($background_color, ['fff', 'ffffff'], true))
            {
                $classes[] = 'custom-background-white';
            }
        }

        // Enable custom font class only if the font CSS is queued to load.
        if(wp_style_is('twentytwelve-fonts', 'queue'))
        {
            $classes[] = 'custom-font-enabled';
        }

        if(! is_multi_author())
        {
            $classes[] = 'single-author';
        }

        return $classes;
    }

    add_filter('body_class', 'twentytwelve_body_class');

    /**
     * Adjust content width in certain contexts.
     *
     * Adjusts content_width value for full-width and single image attachment
     * templates, and when there are no active widgets in the sidebar.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_content_width()
    {
        if(is_page_template('page-templates/full-width.php') || is_attachment() || ! is_active_sidebar('sidebar-1'))
        {
            global $content_width;
            $content_width = 960;
        }
    }

    add_action('template_redirect', 'twentytwelve_content_width');

    /**
     * Register postMessage support.
     *
     * Add postMessage support for site title and description for the Customizer.
     *
     * @param WP_Customize_Manager $wp_customize Customizer object.
     *
     * @since Twenty Twelve 1.0
     *
     */
    function twentytwelve_customize_register($wp_customize)
    {
        $wp_customize->get_setting('blogname')->transport = 'postMessage';
        $wp_customize->get_setting('blogdescription')->transport = 'postMessage';
        $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';

        if(isset($wp_customize->selective_refresh))
        {
            $wp_customize->selective_refresh->add_partial('blogname', [
                'selector' => '.site-title > a',
                'container_inclusive' => false,
                'render_callback' => 'twentytwelve_customize_partial_blogname',
            ]);
            $wp_customize->selective_refresh->add_partial('blogdescription', [
                'selector' => '.site-description',
                'container_inclusive' => false,
                'render_callback' => 'twentytwelve_customize_partial_blogdescription',
            ]);
        }
    }

    add_action('customize_register', 'twentytwelve_customize_register');

    /**
     * Render the site title for the selective refresh partial.
     *
     * @return void
     * @see   twentytwelve_customize_register()
     *
     * @since Twenty Twelve 2.0
     *
     */
    function twentytwelve_customize_partial_blogname()
    {
        bloginfo('name');
    }

    /**
     * Render the site tagline for the selective refresh partial.
     *
     * @return void
     * @see   twentytwelve_customize_register()
     *
     * @since Twenty Twelve 2.0
     *
     */
    function twentytwelve_customize_partial_blogdescription()
    {
        bloginfo('description');
    }

    /**
     * Enqueue JavaScript postMessage handlers for the Customizer.
     *
     * Binds JS handlers to make the Customizer preview reload changes asynchronously.
     *
     * @since Twenty Twelve 1.0
     */
    function twentytwelve_customize_preview_js()
    {
        wp_enqueue_script('twentytwelve-customizer', get_template_directory_uri().'/js/theme-customizer.js', ['customize-preview'], '20200516', ['in_footer' => true]);
    }

    add_action('customize_preview_init', 'twentytwelve_customize_preview_js');

    /**
     * Modifies tag cloud widget arguments to display all tags in the same font size
     * and use list format for better accessibility.
     *
     * @param array $args Arguments for tag cloud widget.
     *
     * @return array The filtered arguments for tag cloud widget.
     * @since Twenty Twelve 2.4
     *
     */
    function twentytwelve_widget_tag_cloud_args($args)
    {
        $args['largest'] = 22;
        $args['smallest'] = 8;
        $args['unit'] = 'pt';
        $args['format'] = 'list';

        return $args;
    }

    add_filter('widget_tag_cloud_args', 'twentytwelve_widget_tag_cloud_args');

    if(! function_exists('wp_body_open')) :
        /**
         * Fire the wp_body_open action.
         *
         * Added for backward compatibility to support pre-5.2.0 WordPress versions.
         *
         * @since Twenty Twelve 3.0
         */
        function wp_body_open()
        {
            /**
             * Triggered after the opening <body> tag.
             *
             * @since Twenty Twelve 3.0
             */
            do_action('wp_body_open');
        }
    endif;
