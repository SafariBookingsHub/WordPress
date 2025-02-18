<?php
    /**
     * REST API: WP_REST_Global_Styles_Controller class
     *
     * @package    WordPress
     * @subpackage REST_API
     * @since      5.9.0
     */

    /**
     * Base Global Styles REST API Controller.
     */
    class WP_REST_Global_Styles_Controller extends WP_REST_Controller
    {
        /**
         * Post type.
         *
         * @since 5.9.0
         * @var string
         */
        protected $post_type;

        /**
         * Constructor.
         *
         * @since 5.9.0
         */
        public function __construct()
        {
            $this->namespace = 'wp/v2';
            $this->rest_base = 'global-styles';
            $this->post_type = 'wp_global_styles';
        }

        /**
         * Registers the controllers routes.
         *
         * @since 5.9.0
         */
        public function register_routes()
        {
            register_rest_route($this->namespace, '/'.$this->rest_base.'/themes/(?P<stylesheet>[\/\s%\w\.\(\)\[\]\@_\-]+)/variations', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_theme_items'],
                    'permission_callback' => [$this, 'get_theme_items_permissions_check'],
                    'args' => [
                        'stylesheet' => [
                            'description' => __('The theme identifier'),
                            'type' => 'string',
                        ],
                    ],
                ],
            ]);

            // List themes global styles.
            register_rest_route(
                $this->namespace, // The route.
                sprintf(
                    '/%s/themes/(?P<stylesheet>%s)', $this->rest_base, /*
                     * Matches theme's directory: `/themes/<subdirectory>/<theme>/` or `/themes/<theme>/`.
                     * Excludes invalid directory name characters: `/:<>*?"|`.
                     */ '[^\/:<>\*\?"\|]+(?:\/[^\/:<>\*\?"\|]+)?'
                ), [
                    [
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => [$this, 'get_theme_item'],
                        'permission_callback' => [$this, 'get_theme_item_permissions_check'],
                        'args' => [
                            'stylesheet' => [
                                'description' => __('The theme identifier'),
                                'type' => 'string',
                                'sanitize_callback' => [$this, '_sanitize_global_styles_callback'],
                            ],
                        ],
                    ],
                ]
            );

            // Lists/updates a single global style variation based on the given id.
            register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\/\w-]+)', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => [
                        'id' => [
                            'description' => __('The id of a template'),
                            'type' => 'string',
                            'sanitize_callback' => [$this, '_sanitize_global_styles_callback'],
                        ],
                    ],
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ],
                'schema' => [$this, 'get_public_item_schema'],
            ]);
        }

        /**
         * Sanitize the global styles ID or stylesheet to decode endpoint.
         * For example, `wp/v2/global-styles/twentytwentytwo%200.4.0`
         * would be decoded to `twentytwentytwo 0.4.0`.
         *
         * @param string $id_or_stylesheet Global styles ID or stylesheet.
         *
         * @return string Sanitized global styles ID or stylesheet.
         * @since 5.9.0
         *
         */
        public function _sanitize_global_styles_callback($id_or_stylesheet)
        {
            return urldecode($id_or_stylesheet);
        }

        /**
         * Checks if a given request has access to read a single global style.
         *
         * @param WP_REST_Request $request Full details about the request.
         *
         * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
         * @since 5.9.0
         *
         */
        public function get_item_permissions_check($request)
        {
            $post = $this->get_post($request['id']);
            if(is_wp_error($post))
            {
                return $post;
            }

            if('edit' === $request['context'] && $post && ! $this->check_update_permission($post))
            {
                return new WP_Error('rest_forbidden_context', __('Sorry, you are not allowed to edit this global style.'), ['status' => rest_authorization_required_code()]);
            }

            if(! $this->check_read_permission($post))
            {
                return new WP_Error('rest_cannot_view', __('Sorry, you are not allowed to view this global style.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        /**
         * Get the post, if the ID is valid.
         *
         * @param int $id Supplied ID.
         *
         * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
         * @since 5.9.0
         *
         */
        protected function get_post($id)
        {
            $error = new WP_Error('rest_global_styles_not_found', __('No global styles config exist with that id.'), ['status' => 404]);

            $id = (int) $id;
            if($id <= 0)
            {
                return $error;
            }

            $post = get_post($id);
            if(empty($post) || empty($post->ID) || $this->post_type !== $post->post_type)
            {
                return $error;
            }

            return $post;
        }

        /**
         * Checks if a global style can be edited.
         *
         * @param WP_Post $post Post object.
         *
         * @return bool Whether the post can be edited.
         * @since 5.9.0
         *
         */
        protected function check_update_permission($post)
        {
            return current_user_can('edit_post', $post->ID);
        }

        /**
         * Checks if a global style can be read.
         *
         * @param WP_Post $post Post object.
         *
         * @return bool Whether the post can be read.
         * @since 5.9.0
         *
         */
        protected function check_read_permission($post)
        {
            return current_user_can('read_post', $post->ID);
        }

        /**
         * Returns the given global styles config.
         *
         * @param WP_REST_Request $request The request instance.
         *
         * @return WP_REST_Response|WP_Error
         * @since 5.9.0
         *
         */
        public function get_item($request)
        {
            $post = $this->get_post($request['id']);
            if(is_wp_error($post))
            {
                return $post;
            }

            return $this->prepare_item_for_response($post, $request);
        }

        /**
         * Prepare a global styles config output for response.
         *
         * @param WP_Post         $post    Global Styles post object.
         * @param WP_REST_Request $request Request object.
         *
         * @return WP_REST_Response Response object.
         * @since 5.9.0
         *
         */
        public function prepare_item_for_response($post, $request)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            $raw_config = json_decode($post->post_content, true);
            $is_global_styles_user_theme_json = isset($raw_config['isGlobalStylesUserThemeJSON']) && true === $raw_config['isGlobalStylesUserThemeJSON'];
            $config = [];
            if($is_global_styles_user_theme_json)
            {
                $config = (new WP_Theme_JSON($raw_config, 'custom'))->get_raw_data();
            }

            // Base fields for every post.
            $fields = $this->get_fields_for_response($request);
            $data = [];

            if(rest_is_field_included('id', $fields))
            {
                $data['id'] = $post->ID;
            }

            if(rest_is_field_included('title', $fields))
            {
                $data['title'] = [];
            }
            if(rest_is_field_included('title.raw', $fields))
            {
                $data['title']['raw'] = $post->post_title;
            }
            if(rest_is_field_included('title.rendered', $fields))
            {
                add_filter('protected_title_format', [$this, 'protected_title_format']);

                $data['title']['rendered'] = get_the_title($post->ID);

                remove_filter('protected_title_format', [$this, 'protected_title_format']);
            }

            if(rest_is_field_included('settings', $fields))
            {
                $data['settings'] = ! empty($config['settings']) && $is_global_styles_user_theme_json ? $config['settings'] : new stdClass();
            }

            if(rest_is_field_included('styles', $fields))
            {
                $data['styles'] = ! empty($config['styles']) && $is_global_styles_user_theme_json ? $config['styles'] : new stdClass();
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);

            // Wrap the data in a response object.
            $response = rest_ensure_response($data);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $links = $this->prepare_links($post->ID);
                $response->add_links($links);
                if(! empty($links['self']['href']))
                {
                    $actions = $this->get_available_actions();
                    $self = $links['self']['href'];
                    foreach($actions as $rel)
                    {
                        $response->add_link($rel, $self);
                    }
                }
            }

            return $response;
        }

        /**
         * Prepares links for the request.
         *
         * @param integer $id ID.
         *
         * @return array Links for the given post.
         * @since 5.9.0
         * @since 6.3.0 Adds revisions count and rest URL href to version-history.
         *
         */
        protected function prepare_links($id)
        {
            $base = sprintf('%s/%s', $this->namespace, $this->rest_base);

            $links = [
                'self' => [
                    'href' => rest_url(trailingslashit($base).$id),
                ],
            ];

            if(post_type_supports($this->post_type, 'revisions'))
            {
                $revisions = wp_get_latest_revision_id_and_total_count($id);
                $revisions_count = ! is_wp_error($revisions) ? $revisions['count'] : 0;
                $revisions_base = sprintf('/%s/%d/revisions', $base, $id);
                $links['version-history'] = [
                    'href' => rest_url($revisions_base),
                    'count' => $revisions_count,
                ];
            }

            return $links;
        }

        /**
         * Get the link relations available for the post and current user.
         *
         * @return array List of link relations.
         * @since 6.2.0 Added 'edit-css' action.
         *
         * @since 5.9.0
         */
        protected function get_available_actions()
        {
            $rels = [];

            $post_type = get_post_type_object($this->post_type);
            if(current_user_can($post_type->cap->publish_posts))
            {
                $rels[] = 'https://api.w.org/action-publish';
            }

            if(current_user_can('edit_css'))
            {
                $rels[] = 'https://api.w.org/action-edit-css';
            }

            return $rels;
        }

        /**
         * Checks if a given request has access to write a single global styles config.
         *
         * @param WP_REST_Request $request Full details about the request.
         *
         * @return true|WP_Error True if the request has write access for the item, WP_Error object otherwise.
         * @since 5.9.0
         *
         */
        public function update_item_permissions_check($request)
        {
            $post = $this->get_post($request['id']);
            if(is_wp_error($post))
            {
                return $post;
            }

            if($post && ! $this->check_update_permission($post))
            {
                return new WP_Error('rest_cannot_edit', __('Sorry, you are not allowed to edit this global style.'), ['status' => rest_authorization_required_code()]);
            }

            return true;
        }

        /**
         * Updates a single global style config.
         *
         * @param WP_REST_Request $request Full details about the request.
         *
         * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
         * @since 5.9.0
         *
         */
        public function update_item($request)
        {
            $post_before = $this->get_post($request['id']);
            if(is_wp_error($post_before))
            {
                return $post_before;
            }

            $changes = $this->prepare_item_for_database($request);
            if(is_wp_error($changes))
            {
                return $changes;
            }

            $result = wp_update_post(wp_slash((array) $changes), true, false);
            if(is_wp_error($result))
            {
                return $result;
            }

            $post = get_post($request['id']);
            $fields_update = $this->update_additional_fields_for_object($post, $request);
            if(is_wp_error($fields_update))
            {
                return $fields_update;
            }

            wp_after_insert_post($post, true, $post_before);

            $response = $this->prepare_item_for_response($post, $request);

            return rest_ensure_response($response);
        }

        /**
         * Prepares a single global styles config for update.
         *
         * @param WP_REST_Request $request Request object.
         *
         * @return stdClass|WP_Error Prepared item on success. WP_Error on when the custom CSS is not valid.
         * @since 5.9.0
         * @since 6.2.0 Added validation of styles.css property.
         *
         */
        protected function prepare_item_for_database($request)
        {
            $changes = new stdClass();
            $changes->ID = $request['id'];

            $post = get_post($request['id']);
            $existing_config = [];
            if($post)
            {
                $existing_config = json_decode($post->post_content, true);
                $json_decoding_error = json_last_error();
                if(JSON_ERROR_NONE !== $json_decoding_error || ! isset($existing_config['isGlobalStylesUserThemeJSON']) || ! $existing_config['isGlobalStylesUserThemeJSON'])
                {
                    $existing_config = [];
                }
            }

            if(isset($request['styles']) || isset($request['settings']))
            {
                $config = [];
                if(isset($request['styles']))
                {
                    if(isset($request['styles']['css']))
                    {
                        $css_validation_result = $this->validate_custom_css($request['styles']['css']);
                        if(is_wp_error($css_validation_result))
                        {
                            return $css_validation_result;
                        }
                    }
                    $config['styles'] = $request['styles'];
                }
                elseif(isset($existing_config['styles']))
                {
                    $config['styles'] = $existing_config['styles'];
                }
                if(isset($request['settings']))
                {
                    $config['settings'] = $request['settings'];
                }
                elseif(isset($existing_config['settings']))
                {
                    $config['settings'] = $existing_config['settings'];
                }
                $config['isGlobalStylesUserThemeJSON'] = true;
                $config['version'] = WP_Theme_JSON::LATEST_SCHEMA;
                $changes->post_content = wp_json_encode($config);
            }

            // Post title.
            if(isset($request['title']))
            {
                if(is_string($request['title']))
                {
                    $changes->post_title = $request['title'];
                }
                elseif(! empty($request['title']['raw']))
                {
                    $changes->post_title = $request['title']['raw'];
                }
            }

            return $changes;
        }

        /**
         * Validate style.css as valid CSS.
         *
         * Currently just checks for invalid markup.
         *
         * @param string $css CSS to validate.
         *
         * @return true|WP_Error True if the input was validated, otherwise WP_Error.
         * @since 6.2.0
         * @since 6.4.0 Changed method visibility to protected.
         *
         */
        protected function validate_custom_css($css)
        {
            if(preg_match('#</?\w+#', $css))
            {
                return new WP_Error('rest_custom_css_illegal_markup', __('Markup is not allowed in CSS.'), ['status' => 400]);
            }

            return true;
        }

        /**
         * Overwrites the default protected title format.
         *
         * By default, WordPress will show password protected posts with a title of
         * "Protected: %s", as the REST API communicates the protected status of a post
         * in a machine readable format, we remove the "Protected: " prefix.
         *
         * @return string Protected title format.
         * @since 5.9.0
         *
         */
        public function protected_title_format()
        {
            return '%s';
        }

        /**
         * Retrieves the query params for the global styles collection.
         *
         * @return array Collection parameters.
         * @since 5.9.0
         *
         */
        public function get_collection_params()
        {
            return [];
        }

        /**
         * Retrieves the global styles type' schema, conforming to JSON Schema.
         *
         * @return array Item schema data.
         * @since 5.9.0
         *
         */
        public function get_item_schema()
        {
            if($this->schema)
            {
                return $this->add_additional_fields_schema($this->schema);
            }

            $schema = [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => $this->post_type,
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'description' => __('ID of global styles config.'),
                        'type' => 'string',
                        'context' => ['embed', 'view', 'edit'],
                        'readonly' => true,
                    ],
                    'styles' => [
                        'description' => __('Global styles.'),
                        'type' => ['object'],
                        'context' => ['view', 'edit'],
                    ],
                    'settings' => [
                        'description' => __('Global settings.'),
                        'type' => ['object'],
                        'context' => ['view', 'edit'],
                    ],
                    'title' => [
                        'description' => __('Title of the global styles variation.'),
                        'type' => ['object', 'string'],
                        'default' => '',
                        'context' => ['embed', 'view', 'edit'],
                        'properties' => [
                            'raw' => [
                                'description' => __('Title for the global styles variation, as it exists in the database.'),
                                'type' => 'string',
                                'context' => ['view', 'edit', 'embed'],
                            ],
                            'rendered' => [
                                'description' => __('HTML title for the post, transformed for display.'),
                                'type' => 'string',
                                'context' => ['view', 'edit', 'embed'],
                                'readonly' => true,
                            ],
                        ],
                    ],
                ],
            ];

            $this->schema = $schema;

            return $this->add_additional_fields_schema($this->schema);
        }

        /**
         * Checks if a given request has access to read a single theme global styles config.
         *
         * @param WP_REST_Request $request Full details about the request.
         *
         * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
         * @since 5.9.0
         *
         */
        public function get_theme_item_permissions_check($request)
        {
            /*
             * Verify if the current user has edit_theme_options capability.
             * This capability is required to edit/view/delete templates.
             */
            if(! current_user_can('edit_theme_options'))
            {
                return new WP_Error('rest_cannot_manage_global_styles', __('Sorry, you are not allowed to access the global styles on this site.'), [
                    'status' => rest_authorization_required_code(),
                ]);
            }

            return true;
        }

        /**
         * Returns the given theme global styles config.
         *
         * @param WP_REST_Request $request The request instance.
         *
         * @return WP_REST_Response|WP_Error
         * @since 5.9.0
         *
         */
        public function get_theme_item($request)
        {
            if(get_stylesheet() !== $request['stylesheet'])
            {
                // This endpoint only supports the active theme for now.
                return new WP_Error('rest_theme_not_found', __('Theme not found.'), ['status' => 404]);
            }

            $theme = WP_Theme_JSON_Resolver::get_merged_data('theme');
            $fields = $this->get_fields_for_response($request);
            $data = [];

            if(rest_is_field_included('settings', $fields))
            {
                $data['settings'] = $theme->get_settings();
            }

            if(rest_is_field_included('styles', $fields))
            {
                $raw_data = $theme->get_raw_data();
                $data['styles'] = isset($raw_data['styles']) ? $raw_data['styles'] : [];
            }

            $context = ! empty($request['context']) ? $request['context'] : 'view';
            $data = $this->add_additional_fields_to_object($data, $request);
            $data = $this->filter_response_by_context($data, $context);

            $response = rest_ensure_response($data);

            if(rest_is_field_included('_links', $fields) || rest_is_field_included('_embedded', $fields))
            {
                $links = [
                    'self' => [
                        'href' => rest_url(sprintf('%s/%s/themes/%s', $this->namespace, $this->rest_base, $request['stylesheet'])),
                    ],
                ];
                $response->add_links($links);
            }

            return $response;
        }

        /**
         * Checks if a given request has access to read a single theme global styles config.
         *
         * @param WP_REST_Request $request Full details about the request.
         *
         * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
         * @since 6.0.0
         *
         */
        public function get_theme_items_permissions_check($request)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            /*
             * Verify if the current user has edit_theme_options capability.
             * This capability is required to edit/view/delete templates.
             */
            if(! current_user_can('edit_theme_options'))
            {
                return new WP_Error('rest_cannot_manage_global_styles', __('Sorry, you are not allowed to access the global styles on this site.'), [
                    'status' => rest_authorization_required_code(),
                ]);
            }

            return true;
        }

        /**
         * Returns the given theme global styles variations.
         *
         * @param WP_REST_Request $request The request instance.
         *
         * @return WP_REST_Response|WP_Error
         * @since 6.0.0
         * @since 6.2.0 Returns parent theme variations, if they exist.
         *
         */
        public function get_theme_items($request)
        {
            if(get_stylesheet() !== $request['stylesheet'])
            {
                // This endpoint only supports the active theme for now.
                return new WP_Error('rest_theme_not_found', __('Theme not found.'), ['status' => 404]);
            }

            $variations = WP_Theme_JSON_Resolver::get_style_variations();

            return rest_ensure_response($variations);
        }
    }
