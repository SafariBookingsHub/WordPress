<?php
    /**
     * Dependencies API: WP_Scripts class
     *
     * @since      2.6.0
     *
     * @package    WordPress
     * @subpackage Dependencies
     */

    /**
     * Core class used to register scripts.
     *
     * @since 2.1.0
     *
     * @see   WP_Dependencies
     */
    class WP_Scripts extends WP_Dependencies
    {
        /**
         * Base URL for scripts.
         *
         * Full URL with trailing slash.
         *
         * @since 2.6.0
         * @var string
         */
        public $base_url;

        /**
         * URL of the content directory.
         *
         * @since 2.8.0
         * @var string
         */
        public $content_url;

        /**
         * Default version string for scripts.
         *
         * @since 2.6.0
         * @var string
         */
        public $default_version;

        /**
         * Holds handles of scripts which are enqueued in footer.
         *
         * @since 2.8.0
         * @var array
         */
        public $in_footer = [];

        /**
         * Holds a list of script handles which will be concatenated.
         *
         * @since 2.8.0
         * @var string
         */
        public $concat = '';

        /**
         * Holds a string which contains script handles and their version.
         *
         * @since      2.8.0
         * @deprecated 3.4.0
         * @var string
         */
        public $concat_version = '';

        /**
         * Whether to perform concatenation.
         *
         * @since 2.8.0
         * @var bool
         */
        public $do_concat = false;

        /**
         * Holds HTML markup of scripts and additional data if concatenation
         * is enabled.
         *
         * @since 2.8.0
         * @var string
         */
        public $print_html = '';

        /**
         * Holds inline code if concatenation is enabled.
         *
         * @since 2.8.0
         * @var string
         */
        public $print_code = '';

        /**
         * Holds a list of script handles which are not in the default directory
         * if concatenation is enabled.
         *
         * Unused in core.
         *
         * @since 2.8.0
         * @var string
         */
        public $ext_handles = '';

        /**
         * Holds a string which contains handles and versions of scripts which
         * are not in the default directory if concatenation is enabled.
         *
         * Unused in core.
         *
         * @since 2.8.0
         * @var string
         */
        public $ext_version = '';

        /**
         * List of default directories.
         *
         * @since 2.8.0
         * @var array
         */
        public $default_dirs;

        /**
         * Holds a string which contains the type attribute for script tag.
         *
         * If the active theme does not declare HTML5 support for 'script',
         * then it initializes as `type='text/javascript'`.
         *
         * @since 5.3.0
         * @var string
         */
        private $type_attr = '';

        /**
         * Holds a mapping of dependents (as handles) for a given script handle.
         * Used to optimize recursive dependency tree checks.
         *
         * @since 6.3.0
         * @var array
         */
        private $dependents_map = [];

        /**
         * Holds a reference to the delayed (non-blocking) script loading strategies.
         * Used by methods that validate loading strategies.
         *
         * @since 6.3.0
         * @var string[]
         */
        private $delayed_strategies = ['defer', 'async'];

        /**
         * Constructor.
         *
         * @since 2.6.0
         */
        public function __construct()
        {
            $this->init();
            add_action('init', [$this, 'init'], 0);
        }

        /**
         * Initialize the class.
         *
         * @since 3.4.0
         */
        public function init()
        {
            if(function_exists('is_admin') && ! is_admin() && function_exists('current_theme_supports') && ! current_theme_supports('html5', 'script'))
            {
                $this->type_attr = " type='text/javascript'";
            }

            /**
             * Fires when the WP_Scripts instance is initialized.
             *
             * @param WP_Scripts $wp_scripts WP_Scripts instance (passed by reference).
             *
             * @since 2.6.0
             *
             */
            do_action_ref_array('wp_default_scripts', [&$this]);
        }

        /**
         * Prints scripts.
         *
         * Prints the scripts passed to it or the print queue. Also prints all necessary dependencies.
         *
         * @param string|string[]|false $handles Optional. Scripts to be printed: queue (false),
         *                                       single script (string), or multiple scripts (array of strings).
         *                                       Default false.
         * @param int|false             $group   Optional. Group level: level (int), no groups (false).
         *                                       Default false.
         *
         * @return string[] Handles of scripts that have been printed.
         * @since 2.8.0 Added the `$group` parameter.
         *
         * @since 2.1.0
         */
        public function print_scripts($handles = false, $group = false)
        {
            return $this->do_items($handles, $group);
        }

        /**
         * Prints extra scripts of a registered script.
         *
         * @param string $handle  The script's registered handle.
         * @param bool   $display Optional. Whether to print the extra script
         *                        instead of just returning it. Default true.
         *
         * @return bool|string|void Void if no data exists, extra scripts if `$display` is true,
         *                          true otherwise.
         * @see        print_extra_script()
         *
         * @since      2.1.0
         * @since      2.8.0 Added the `$display` parameter.
         * @deprecated 3.3.0
         *
         */
        public function print_scripts_l10n($handle, $display = true)
        {
            _deprecated_function(__FUNCTION__, '3.3.0', 'WP_Scripts::print_extra_script()');

            return $this->print_extra_script($handle, $display);
        }

        /**
         * Prints extra scripts of a registered script.
         *
         * @param string $handle  The script's registered handle.
         * @param bool   $display Optional. Whether to print the extra script
         *                        instead of just returning it. Default true.
         *
         * @return bool|string|void Void if no data exists, extra scripts if `$display` is true,
         *                          true otherwise.
         * @since 3.3.0
         *
         */
        public function print_extra_script($handle, $display = true)
        {
            $output = $this->get_data($handle, 'data');
            if(! $output)
            {
                return;
            }

            if(! $display)
            {
                return $output;
            }

            printf("<script%s id='%s-js-extra'>\n", $this->type_attr, esc_attr($handle));

            // CDATA is not needed for HTML 5.
            if($this->type_attr)
            {
                echo "/* <![CDATA[ */\n";
            }

            echo "$output\n";

            if($this->type_attr)
            {
                echo "/* ]]> */\n";
            }

            echo "</script>\n";

            return true;
        }

        /**
         * Processes a script dependency.
         *
         * @param string    $handle The script's registered handle.
         * @param int|false $group  Optional. Group level: level (int), no groups (false).
         *                          Default false.
         *
         * @return bool True on success, false on failure.
         * @since 2.6.0
         * @since 2.8.0 Added the `$group` parameter.
         *
         * @see   WP_Dependencies::do_item()
         *
         */
        public function do_item($handle, $group = false)
        {
            if(! parent::do_item($handle))
            {
                return false;
            }

            if(0 === $group && $this->groups[$handle] > 0)
            {
                $this->in_footer[] = $handle;

                return false;
            }

            if(false === $group && in_array($handle, $this->in_footer, true))
            {
                $this->in_footer = array_diff($this->in_footer, (array) $handle);
            }

            $obj = $this->registered[$handle];

            if(null === $obj->ver)
            {
                $ver = '';
            }
            else
            {
                $ver = $obj->ver ? $obj->ver : $this->default_version;
            }

            if(isset($this->args[$handle]))
            {
                $ver = $ver ? $ver.'&amp;'.$this->args[$handle] : $this->args[$handle];
            }

            $src = $obj->src;
            $strategy = $this->get_eligible_loading_strategy($handle);
            $intended_strategy = (string) $this->get_data($handle, 'strategy');
            $cond_before = '';
            $cond_after = '';
            $conditional = isset($obj->extra['conditional']) ? $obj->extra['conditional'] : '';

            if(! $this->is_delayed_strategy($intended_strategy))
            {
                $intended_strategy = '';
            }

            if($conditional)
            {
                $cond_before = "<!--[if {$conditional}]>\n";
                $cond_after = "<![endif]-->\n";
            }

            $before_script = $this->get_inline_script_tag($handle, 'before');
            $after_script = $this->get_inline_script_tag($handle, 'after');

            if($before_script || $after_script)
            {
                $inline_script_tag = $cond_before.$before_script.$after_script.$cond_after;
            }
            else
            {
                $inline_script_tag = '';
            }

            /*
             * Prevent concatenation of scripts if the text domain is defined
             * to ensure the dependency order is respected.
             */
            $translations_stop_concat = ! empty($obj->textdomain);

            $translations = $this->print_translations($handle, false);
            if($translations)
            {
                $translations = sprintf("<script%s id='%s-js-translations'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $translations);
            }

            if($this->do_concat)
            {
                /**
                 * Filters the script loader source.
                 *
                 * @param string $src    Script loader source path.
                 * @param string $handle Script handle.
                 *
                 * @since 2.2.0
                 *
                 */
                $srce = apply_filters('script_loader_src', $src, $handle);

                if($this->in_default_dir($srce) && ($before_script || $after_script || $translations_stop_concat || $this->is_delayed_strategy($strategy)))
                {
                    $this->do_concat = false;

                    // Have to print the so-far concatenated scripts right away to maintain the right order.
                    _print_scripts();
                    $this->reset();
                }
                elseif($this->in_default_dir($srce) && ! $conditional)
                {
                    $this->print_code .= $this->print_extra_script($handle, false);
                    $this->concat .= "$handle,";
                    $this->concat_version .= "$handle$ver";

                    return true;
                }
                else
                {
                    $this->ext_handles .= "$handle,";
                    $this->ext_version .= "$handle$ver";
                }
            }

            $has_conditional_data = $conditional && $this->get_data($handle, 'data');

            if($has_conditional_data)
            {
                echo $cond_before;
            }

            $this->print_extra_script($handle);

            if($has_conditional_data)
            {
                echo $cond_after;
            }

            // A single item may alias a set of items, by having dependencies, but no source.
            if(! $src)
            {
                if($inline_script_tag)
                {
                    if($this->do_concat)
                    {
                        $this->print_html .= $inline_script_tag;
                    }
                    else
                    {
                        echo $inline_script_tag;
                    }
                }

                return true;
            }

            if(! preg_match('|^(https?:)?//|', $src) && ! ($this->content_url && str_starts_with($src, $this->content_url)))
            {
                $src = $this->base_url.$src;
            }

            if(! empty($ver))
            {
                $src = add_query_arg('ver', $ver, $src);
            }

            /** This filter is documented in wp-includes/class-wp-scripts.php */
            $src = esc_url(apply_filters('script_loader_src', $src, $handle));

            if(! $src)
            {
                return true;
            }

            $tag = $translations.$cond_before.$before_script;
            $tag .= sprintf(
                "<script%s src='%s' id='%s-js'%s%s></script>\n", $this->type_attr, $src, // Value is escaped above.
                esc_attr($handle), $strategy ? " {$strategy}" : '', $intended_strategy ? " data-wp-strategy='{$intended_strategy}'" : ''
            );
            $tag .= $after_script.$cond_after;

            /**
             * Filters the HTML script tag of an enqueued script.
             *
             * @param string $tag    The `<script>` tag for the enqueued script.
             * @param string $handle The script's registered handle.
             * @param string $src    The script's source URL.
             *
             * @since 4.1.0
             *
             */
            $tag = apply_filters('script_loader_tag', $tag, $handle, $src);

            if($this->do_concat)
            {
                $this->print_html .= $tag;
            }
            else
            {
                echo $tag;
            }

            return true;
        }

        /**
         * Gets the best eligible loading strategy for a script.
         *
         * @param string $handle The script handle.
         *
         * @return string The best eligible loading strategy.
         * @since 6.3.0
         *
         */
        private function get_eligible_loading_strategy($handle)
        {
            $intended = (string) $this->get_data($handle, 'strategy');

            // Bail early if there is no intended strategy.
            if(! $intended)
            {
                return '';
            }

            /*
             * If the intended strategy is 'defer', limit the initial list of eligible
             * strategies, since 'async' can fallback to 'defer', but not vice-versa.
             */
            $initial = ('defer' === $intended) ? ['defer'] : null;

            $eligible = $this->filter_eligible_strategies($handle, $initial);

            // Return early once we know the eligible strategy is blocking.
            if(empty($eligible))
            {
                return '';
            }

            return in_array('async', $eligible, true) ? 'async' : 'defer';
        }

        /**
         * Filter the list of eligible loading strategies for a script.
         *
         * @param string              $handle   The script handle.
         * @param string[]|null       $eligible Optional. The list of strategies to filter. Default null.
         * @param array<string, true> $checked  Optional. An array of already checked script handles, used to avoid
         *                                      recursive loops.
         *
         * @return string[] A list of eligible loading strategies that could be used.
         * @since 6.3.0
         *
         */
        private function filter_eligible_strategies($handle, $eligible = null, $checked = [])
        {
            // If no strategies are being passed, all strategies are eligible.
            if(null === $eligible)
            {
                $eligible = $this->delayed_strategies;
            }

            // If this handle was already checked, return early.
            if(isset($checked[$handle]))
            {
                return $eligible;
            }

            // Mark this handle as checked.
            $checked[$handle] = true;

            // If this handle isn't registered, don't filter anything and return.
            if(! isset($this->registered[$handle]))
            {
                return $eligible;
            }

            // If the handle is not enqueued, don't filter anything and return.
            if(! $this->query($handle, 'enqueued'))
            {
                return $eligible;
            }

            $is_alias = (bool) ! $this->registered[$handle]->src;
            $intended_strategy = $this->get_data($handle, 'strategy');

            // For non-alias handles, an empty intended strategy filters all strategies.
            if(! $is_alias && empty($intended_strategy))
            {
                return [];
            }

            // Handles with inline scripts attached in the 'after' position cannot be delayed.
            if($this->has_inline_script($handle, 'after'))
            {
                return [];
            }

            // If the intended strategy is 'defer', filter out 'async'.
            if('defer' === $intended_strategy)
            {
                $eligible = ['defer'];
            }

            $dependents = $this->get_dependents($handle);

            // Recursively filter eligible strategies for dependents.
            foreach($dependents as $dependent)
            {
                // Bail early once we know the eligible strategy is blocking.
                if(empty($eligible))
                {
                    return [];
                }

                $eligible = $this->filter_eligible_strategies($dependent, $eligible, $checked);
            }

            return $eligible;
        }

        /**
         * Gets data for inline scripts registered for a specific handle.
         *
         * @param string $handle   Name of the script to get data for. Must be lowercase.
         * @param string $position The position of the inline script.
         *
         * @return bool Whether the handle has an inline script (either before or after).
         * @since 6.3.0
         *
         */
        private function has_inline_script($handle, $position = null)
        {
            if($position && in_array($position, ['before', 'after'], true))
            {
                return (bool) $this->get_data($handle, $position);
            }

            return (bool) ($this->get_data($handle, 'before') || $this->get_data($handle, 'after'));
        }

        /**
         * Gets all dependents of a script.
         *
         * @param string $handle The script handle.
         *
         * @return string[] Script handles.
         * @since 6.3.0
         *
         */
        private function get_dependents($handle)
        {
            // Check if dependents map for the handle in question is present. If so, use it.
            if(isset($this->dependents_map[$handle]))
            {
                return $this->dependents_map[$handle];
            }

            $dependents = [];

            // Iterate over all registered scripts, finding dependents of the script passed to this method.
            foreach($this->registered as $registered_handle => $args)
            {
                if(in_array($handle, $args->deps, true))
                {
                    $dependents[] = $registered_handle;
                }
            }

            // Add the handles dependents to the map to ease future lookups.
            $this->dependents_map[$handle] = $dependents;

            return $dependents;
        }

        /**
         * Checks if the strategy passed is a valid delayed (non-blocking) strategy.
         *
         * @param string $strategy The strategy to check.
         *
         * @return bool True if $strategy is one of the delayed strategies, otherwise false.
         * @since 6.3.0
         *
         */
        private function is_delayed_strategy($strategy)
        {
            return in_array($strategy, $this->delayed_strategies, true);
        }

        /**
         * Gets tags for inline scripts registered for a specific handle.
         *
         * @param string $handle   Name of the script to get associated inline script tag for.
         *                         Must be lowercase.
         * @param string $position Optional. Whether to get tag for inline
         *                         scripts in the before or after position. Default 'after'.
         *
         * @return string Inline script, which may be empty string.
         * @since 6.3.0
         *
         */
        public function get_inline_script_tag($handle, $position = 'after')
        {
            $js = $this->get_inline_script_data($handle, $position);
            if(empty($js))
            {
                return '';
            }

            $id = "{$handle}-js-{$position}";

            return wp_get_inline_script_tag($js, compact('id'));
        }

        /**
         * Gets data for inline scripts registered for a specific handle.
         *
         * @param string $handle   Name of the script to get data for.
         *                         Must be lowercase.
         * @param string $position Optional. Whether to add the inline script
         *                         before the handle or after. Default 'after'.
         *
         * @return string Inline script, which may be empty string.
         * @since 6.3.0
         *
         */
        public function get_inline_script_data($handle, $position = 'after')
        {
            $data = $this->get_data($handle, $position);
            if(empty($data) || ! is_array($data))
            {
                return '';
            }

            return trim(implode("\n", $data), "\n");
        }

        /**
         * Prints translations set for a specific handle.
         *
         * @param string $handle  Name of the script to add the inline script to.
         *                        Must be lowercase.
         * @param bool   $display Optional. Whether to print the script
         *                        instead of just returning it. Default true.
         *
         * @return string|false Script on success, false otherwise.
         * @since 5.0.0
         *
         */
        public function print_translations($handle, $display = true)
        {
            if(! isset($this->registered[$handle]) || empty($this->registered[$handle]->textdomain))
            {
                return false;
            }

            $domain = $this->registered[$handle]->textdomain;
            $path = '';

            if(isset($this->registered[$handle]->translations_path))
            {
                $path = $this->registered[$handle]->translations_path;
            }

            $json_translations = load_script_textdomain($handle, $domain, $path);

            if(! $json_translations)
            {
                return false;
            }

            $output = <<<JS
( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( "{$domain}", {$json_translations} );
JS;

            if($display)
            {
                printf("<script%s id='%s-js-translations'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $output);
            }

            return $output;
        }

        /**
         * Whether a handle's source is in a default directory.
         *
         * @param string $src The source of the enqueued script.
         *
         * @return bool True if found, false if not.
         * @since 2.8.0
         *
         */
        public function in_default_dir($src)
        {
            if(! $this->default_dirs)
            {
                return true;
            }

            if(str_starts_with($src, '/'.WPINC.'/js/l10n'))
            {
                return false;
            }

            foreach((array) $this->default_dirs as $test)
            {
                if(str_starts_with($src, $test))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Resets class properties.
         *
         * @since 2.8.0
         */
        public function reset()
        {
            $this->do_concat = false;
            $this->print_code = '';
            $this->concat = '';
            $this->concat_version = '';
            $this->print_html = '';
            $this->ext_version = '';
            $this->ext_handles = '';
        }

        /**
         * Adds extra code to a registered script.
         *
         * @param string $handle   Name of the script to add the inline script to.
         *                         Must be lowercase.
         * @param string $data     String containing the JavaScript to be added.
         * @param string $position Optional. Whether to add the inline script
         *                         before the handle or after. Default 'after'.
         *
         * @return bool True on success, false on failure.
         * @since 4.5.0
         *
         */
        public function add_inline_script($handle, $data, $position = 'after')
        {
            if(! $data)
            {
                return false;
            }

            if('after' !== $position)
            {
                $position = 'before';
            }

            $script = (array) $this->get_data($handle, $position);
            $script[] = $data;

            return $this->add_data($handle, $position, $script);
        }

        /**
         * This overrides the add_data method from WP_Dependencies, to support normalizing of $args.
         *
         * @param string $handle Name of the item. Should be unique.
         * @param string $key    The data key.
         * @param mixed  $value  The data value.
         *
         * @return bool True on success, false on failure.
         * @since 6.3.0
         *
         */
        public function add_data($handle, $key, $value)
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            if('strategy' === $key)
            {
                if(! empty($value) && ! $this->is_delayed_strategy($value))
                {
                    _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $strategy, 2: $handle */ __('Invalid strategy `%1$s` defined for `%2$s` during script registration.'), $value, $handle), '6.3.0');

                    return false;
                }
                elseif(! $this->registered[$handle]->src && $this->is_delayed_strategy($value))
                {
                    _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $strategy, 2: $handle */ __('Cannot supply a strategy `%1$s` for script `%2$s` because it is an alias (it lacks a `src` value).'), $value, $handle), '6.3.0');

                    return false;
                }
            }

            return parent::add_data($handle, $key, $value);
        }

        /**
         * Prints inline scripts registered for a specific handle.
         *
         * @param string $handle   Name of the script to print inline scripts for.
         *                         Must be lowercase.
         * @param string $position Optional. Whether to add the inline script
         *                         before the handle or after. Default 'after'.
         * @param bool   $display  Optional. Whether to print the script tag
         *                         instead of just returning the script data. Default true.
         *
         * @return string|false Script data on success, false otherwise.
         * @since      4.5.0
         * @deprecated 6.3.0 Use methods get_inline_script_tag() or get_inline_script_data() instead.
         *
         */
        public function print_inline_script($handle, $position = 'after', $display = true)
        {
            _deprecated_function(__METHOD__, '6.3.0', 'WP_Scripts::get_inline_script_data() or WP_Scripts::get_inline_script_tag()');

            $output = $this->get_inline_script_data($handle, $position);
            if(empty($output))
            {
                return false;
            }

            if($display)
            {
                echo $this->get_inline_script_tag($handle, $position);
            }

            return $output;
        }

        /**
         * Localizes a script, only if the script has already been added.
         *
         * @param string $handle      Name of the script to attach data to.
         * @param string $object_name Name of the variable that will contain the data.
         * @param array  $l10n        Array of data to localize.
         *
         * @return bool True on success, false on failure.
         * @since 2.1.0
         *
         */
        public function localize($handle, $object_name, $l10n)
        {
            if('jquery' === $handle)
            {
                $handle = 'jquery-core';
            }

            if(is_array($l10n) && isset($l10n['l10n_print_after']))
            { // back compat, preserve the code in 'l10n_print_after' if present.
                $after = $l10n['l10n_print_after'];
                unset($l10n['l10n_print_after']);
            }

            if(! is_array($l10n))
            {
                _doing_it_wrong(__METHOD__, sprintf(/* translators: 1: $l10n, 2: wp_add_inline_script() */ __('The %1$s parameter must be an array. To pass arbitrary data to scripts, use the %2$s function instead.'), '<code>$l10n</code>', '<code>wp_add_inline_script()</code>'), '5.7.0');

                if(false === $l10n)
                {
                    // This should really not be needed, but is necessary for backward compatibility.
                    $l10n = [$l10n];
                }
            }

            if(is_string($l10n))
            {
                $l10n = html_entity_decode($l10n, ENT_QUOTES, 'UTF-8');
            }
            elseif(is_array($l10n))
            {
                foreach($l10n as $key => $value)
                {
                    if(! is_scalar($value))
                    {
                        continue;
                    }

                    $l10n[$key] = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
                }
            }

            $script = "var $object_name = ".wp_json_encode($l10n).';';

            if(! empty($after))
            {
                $script .= "\n$after;";
            }

            $data = $this->get_data($handle, 'data');

            if(! empty($data))
            {
                $script = "$data\n$script";
            }

            return $this->add_data($handle, 'data', $script);
        }

        /**
         * Sets handle group.
         *
         * @param string    $handle    Name of the item. Should be unique.
         * @param bool      $recursion Internal flag that calling function was called recursively.
         * @param int|false $group     Optional. Group level: level (int), no groups (false).
         *                             Default false.
         *
         * @return bool Not already in the group or a lower group.
         * @since 2.8.0
         *
         * @see   WP_Dependencies::set_group()
         *
         */
        public function set_group($handle, $recursion, $group = false)
        {
            if(isset($this->registered[$handle]->args) && 1 === $this->registered[$handle]->args)
            {
                $grp = 1;
            }
            else
            {
                $grp = (int) $this->get_data($handle, 'group');
            }

            if(false !== $group && $grp > $group)
            {
                $grp = $group;
            }

            return parent::set_group($handle, $recursion, $grp);
        }

        /**
         * Sets a translation textdomain.
         *
         * @param string $handle Name of the script to register a translation domain to.
         * @param string $domain Optional. Text domain. Default 'default'.
         * @param string $path   Optional. The full file path to the directory containing translation files.
         *
         * @return bool True if the text domain was registered, false if not.
         * @since 5.0.0
         * @since 5.1.0 The `$domain` parameter was made optional.
         *
         */
        public function set_translations($handle, $domain = 'default', $path = '')
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            /** @var \_WP_Dependency $obj */
            $obj = $this->registered[$handle];

            if(! in_array('wp-i18n', $obj->deps, true))
            {
                $obj->deps[] = 'wp-i18n';
            }

            return $obj->set_translations($domain, $path);
        }

        /**
         * Determines script dependencies.
         *
         * @param string|string[] $handles   Item handle (string) or item handles (array of strings).
         * @param bool            $recursion Optional. Internal flag that function is calling itself.
         *                                   Default false.
         * @param int|false       $group     Optional. Group level: level (int), no groups (false).
         *                                   Default false.
         *
         * @return bool True on success, false on failure.
         * @since 2.1.0
         *
         * @see   WP_Dependencies::all_deps()
         *
         */
        public function all_deps($handles, $recursion = false, $group = false)
        {
            $r = parent::all_deps($handles, $recursion, $group);
            if(! $recursion)
            {
                /**
                 * Filters the list of script dependencies left to print.
                 *
                 * @param string[] $to_do An array of script dependency handles.
                 *
                 * @since 2.3.0
                 *
                 */
                $this->to_do = apply_filters('print_scripts_array', $this->to_do);
            }

            return $r;
        }

        /**
         * Processes items and dependencies for the head group.
         *
         * @return string[] Handles of items that have been processed.
         * @see   WP_Dependencies::do_items()
         *
         * @since 2.8.0
         *
         */
        public function do_head_items()
        {
            $this->do_items(false, 0);

            return $this->done;
        }

        /**
         * Processes items and dependencies for the footer group.
         *
         * @return string[] Handles of items that have been processed.
         * @see   WP_Dependencies::do_items()
         *
         * @since 2.8.0
         *
         */
        public function do_footer_items()
        {
            $this->do_items(false, 1);

            return $this->done;
        }

        /**
         * Gets unaliased dependencies.
         *
         * An alias is a dependency whose src is false. It is used as a way to bundle multiple dependencies in a single
         * handle. This in effect flattens an alias dependency tree.
         *
         * @param string[] $deps Dependency handles.
         *
         * @return string[] Unaliased handles.
         * @since 6.3.0
         *
         */
        private function get_unaliased_deps(array $deps)
        {
            $flattened = [];
            foreach($deps as $dep)
            {
                if(! isset($this->registered[$dep]))
                {
                    continue;
                }

                if($this->registered[$dep]->src)
                {
                    $flattened[] = $dep;
                }
                elseif($this->registered[$dep]->deps)
                {
                    array_push($flattened, ...$this->get_unaliased_deps($this->registered[$dep]->deps));
                }
            }

            return $flattened;
        }
    }
