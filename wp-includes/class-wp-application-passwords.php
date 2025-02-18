<?php
    /**
     * WP_Application_Passwords class
     *
     * @package WordPress
     * @since   5.6.0
     */

    /**
     * Class for displaying, modifying, and sanitizing application passwords.
     *
     * @package WordPress
     */
    #[AllowDynamicProperties]
    class WP_Application_Passwords
    {
        /**
         * The application passwords user meta key.
         *
         * @since 5.6.0
         *
         * @var string
         */
        const USERMETA_KEY_APPLICATION_PASSWORDS = '_application_passwords';

        /**
         * The option name used to store whether application passwords are in use.
         *
         * @since 5.6.0
         *
         * @var string
         */
        const OPTION_KEY_IN_USE = 'using_application_passwords';

        /**
         * The generated application password length.
         *
         * @since 5.6.0
         *
         * @var int
         */
        const PW_LENGTH = 24;

        /**
         * Checks if application passwords are being used by the site.
         *
         * This returns true if at least one application password has ever been created.
         *
         * @return bool
         * @since 5.6.0
         *
         */
        public static function is_in_use()
        {
            $network_id = get_main_network_id();

            return (bool) get_network_option($network_id, self::OPTION_KEY_IN_USE);
        }

        /**
         * Creates a new application password.
         *
         * @param int   $user_id   User ID.
         * @param array $args      {
         *                         Arguments used to create the application password.
         *
         * @type string $name      The name of the application password.
         * @type string $app_id    A UUID provided by the application to uniquely identify it.
         *                         }
         * @return array|WP_Error {
         *                         Application password details, or a WP_Error instance if an error occurs.
         *
         * @type string $0 The unhashed generated application password.
         * @type array  $1 {
         *                         The details about the created password.
         *
         * @type string $uuid      The unique identifier for the application password.
         * @type string $app_id    A UUID provided by the application to uniquely identify it.
         * @type string $name      The name of the application password.
         * @type string $password  A one-way hash of the password.
         * @type int    $created   Unix timestamp of when the password was created.
         * @type null   $last_used Null.
         * @type null   $last_ip   Null.
         *                         }
         *                         }
         * @since 5.7.0 Returns WP_Error if application name already exists.
         *
         * @since 5.6.0
         */
        public static function create_new_application_password($user_id, $args = [])
        {
            if(! empty($args['name']))
            {
                $args['name'] = sanitize_text_field($args['name']);
            }

            if(empty($args['name']))
            {
                return new WP_Error('application_password_empty_name', __('An application name is required to create an application password.'), ['status' => 400]);
            }

            if(self::application_name_exists_for_user($user_id, $args['name']))
            {
                return new WP_Error('application_password_duplicate_name', __('Each application name should be unique.'), ['status' => 409]);
            }

            $new_password = wp_generate_password(static::PW_LENGTH, false);
            $hashed_password = wp_hash_password($new_password);

            $new_item = [
                'uuid' => wp_generate_uuid4(),
                'app_id' => empty($args['app_id']) ? '' : $args['app_id'],
                'name' => $args['name'],
                'password' => $hashed_password,
                'created' => time(),
                'last_used' => null,
                'last_ip' => null,
            ];

            $passwords = static::get_user_application_passwords($user_id);
            $passwords[] = $new_item;
            $saved = static::set_user_application_passwords($user_id, $passwords);

            if(! $saved)
            {
                return new WP_Error('db_error', __('Could not save application password.'));
            }

            $network_id = get_main_network_id();
            if(! get_network_option($network_id, self::OPTION_KEY_IN_USE))
            {
                update_network_option($network_id, self::OPTION_KEY_IN_USE, true);
            }

            /**
             * Fires when an application password is created.
             *
             * @param int    $user_id      The user ID.
             * @param array  $new_item     {
             *                             The details about the created password.
             *
             * @type string  $uuid         The unique identifier for the application password.
             * @type string  $app_id       A UUID provided by the application to uniquely identify it.
             * @type string  $name         The name of the application password.
             * @type string  $password     A one-way hash of the password.
             * @type int     $created      Unix timestamp of when the password was created.
             * @type null    $last_used    Null.
             * @type null    $last_ip      Null.
             *                             }
             *
             * @param string $new_password The unhashed generated application password.
             * @param array  $args         {
             *                             Arguments used to create the application password.
             *
             * @type string  $name         The name of the application password.
             * @type string  $app_id       A UUID provided by the application to uniquely identify it.
             *                             }
             * @since 5.6.0
             *
             */
            do_action('wp_create_application_password', $user_id, $new_item, $new_password, $args);

            return [$new_password, $new_item];
        }

        /**
         * Checks if an application password with the given name exists for this user.
         *
         * @param int    $user_id User ID.
         * @param string $name    Application name.
         *
         * @return bool Whether the provided application name exists.
         * @since 5.7.0
         *
         */
        public static function application_name_exists_for_user($user_id, $name)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $password)
            {
                if(strtolower($password['name']) === strtolower($name))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Gets a user's application passwords.
         *
         * @param int        $user_id   User ID.
         *
         * @return array {
         *     The list of app passwords.
         *
         * @type array ...$0 {
         * @type string      $uuid      The unique identifier for the application password.
         * @type string      $app_id    A UUID provided by the application to uniquely identify it.
         * @type string      $name      The name of the application password.
         * @type string      $password  A one-way hash of the password.
         * @type int         $created   Unix timestamp of when the password was created.
         * @type int|null    $last_used The Unix timestamp of the GMT date the application password was last used.
         * @type string|null $last_ip   The IP address the application password was last used by.
         *                              }
         *                              }
         * @since 5.6.0
         *
         */
        public static function get_user_application_passwords($user_id)
        {
            $passwords = get_user_meta($user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, true);

            if(! is_array($passwords))
            {
                return [];
            }

            $save = false;

            foreach($passwords as $i => $password)
            {
                if(! isset($password['uuid']))
                {
                    $passwords[$i]['uuid'] = wp_generate_uuid4();
                    $save = true;
                }
            }

            if($save)
            {
                static::set_user_application_passwords($user_id, $passwords);
            }

            return $passwords;
        }

        /**
         * Sets a user's application passwords.
         *
         * @param int   $user_id   User ID.
         * @param array $passwords Application passwords.
         *
         * @return bool
         * @since 5.6.0
         *
         */
        protected static function set_user_application_passwords($user_id, $passwords)
        {
            return update_user_meta($user_id, static::USERMETA_KEY_APPLICATION_PASSWORDS, $passwords);
        }

        /**
         * Gets a user's application password with the given UUID.
         *
         * @param int    $user_id User ID.
         * @param string $uuid    The password's UUID.
         *
         * @return array|null The application password if found, null otherwise.
         * @since 5.6.0
         *
         */
        public static function get_user_application_password($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $password)
            {
                if($password['uuid'] === $uuid)
                {
                    return $password;
                }
            }

            return null;
        }

        /**
         * Updates an application password.
         *
         * @param int    $user_id User ID.
         * @param string $uuid    The password's UUID.
         * @param array  $update  Information about the application password to update.
         *
         * @return true|WP_Error True if successful, otherwise a WP_Error instance is returned on error.
         * @since 5.6.0
         *
         */
        public static function update_application_password($user_id, $uuid, $update = [])
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as &$item)
            {
                if($item['uuid'] !== $uuid)
                {
                    continue;
                }

                if(! empty($update['name']))
                {
                    $update['name'] = sanitize_text_field($update['name']);
                }

                $save = false;

                if(! empty($update['name']) && $item['name'] !== $update['name'])
                {
                    $item['name'] = $update['name'];
                    $save = true;
                }

                if($save)
                {
                    $saved = static::set_user_application_passwords($user_id, $passwords);

                    if(! $saved)
                    {
                        return new WP_Error('db_error', __('Could not save application password.'));
                    }
                }

                /**
                 * Fires when an application password is updated.
                 *
                 * @param int   $user_id The user ID.
                 * @param array $item    The updated app password details.
                 * @param array $update  The information to update.
                 *
                 * @since 5.6.0
                 *
                 */
                do_action('wp_update_application_password', $user_id, $item, $update);

                return true;
            }

            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        /**
         * Records that an application password has been used.
         *
         * @param int    $user_id User ID.
         * @param string $uuid    The password's UUID.
         *
         * @return true|WP_Error True if the usage was recorded, a WP_Error if an error occurs.
         * @since 5.6.0
         *
         */
        public static function record_application_password_usage($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as &$password)
            {
                if($password['uuid'] !== $uuid)
                {
                    continue;
                }

                // Only record activity once a day.
                if($password['last_used'] + DAY_IN_SECONDS > time())
                {
                    return true;
                }

                $password['last_used'] = time();
                $password['last_ip'] = $_SERVER['REMOTE_ADDR'];

                $saved = static::set_user_application_passwords($user_id, $passwords);

                if(! $saved)
                {
                    return new WP_Error('db_error', __('Could not save application password.'));
                }

                return true;
            }

            // Specified application password not found!
            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        /**
         * Deletes an application password.
         *
         * @param int    $user_id User ID.
         * @param string $uuid    The password's UUID.
         *
         * @return true|WP_Error Whether the password was successfully found and deleted, a WP_Error otherwise.
         * @since 5.6.0
         *
         */
        public static function delete_application_password($user_id, $uuid)
        {
            $passwords = static::get_user_application_passwords($user_id);

            foreach($passwords as $key => $item)
            {
                if($item['uuid'] === $uuid)
                {
                    unset($passwords[$key]);
                    $saved = static::set_user_application_passwords($user_id, $passwords);

                    if(! $saved)
                    {
                        return new WP_Error('db_error', __('Could not delete application password.'));
                    }

                    /**
                     * Fires when an application password is deleted.
                     *
                     * @param int   $user_id The user ID.
                     * @param array $item    The data about the application password.
                     *
                     * @since 5.6.0
                     *
                     */
                    do_action('wp_delete_application_password', $user_id, $item);

                    return true;
                }
            }

            return new WP_Error('application_password_not_found', __('Could not find an application password with that id.'));
        }

        /**
         * Deletes all application passwords for the given user.
         *
         * @param int $user_id User ID.
         *
         * @return int|WP_Error The number of passwords that were deleted or a WP_Error on failure.
         * @since 5.6.0
         *
         */
        public static function delete_all_application_passwords($user_id)
        {
            $passwords = static::get_user_application_passwords($user_id);

            if($passwords)
            {
                $saved = static::set_user_application_passwords($user_id, []);

                if(! $saved)
                {
                    return new WP_Error('db_error', __('Could not delete application passwords.'));
                }

                foreach($passwords as $item)
                {
                    /** This action is documented in wp-includes/class-wp-application-passwords.php */
                    do_action('wp_delete_application_password', $user_id, $item);
                }

                return count($passwords);
            }

            return 0;
        }

        /**
         * Sanitizes and then splits a password into smaller chunks.
         *
         * @param string $raw_password The raw application password.
         *
         * @return string The chunked password.
         * @since 5.6.0
         *
         */
        public static function chunk_password($raw_password)
        {
            $raw_password = preg_replace('/[^a-z\d]/i', '', $raw_password);

            return trim(chunk_split($raw_password, 4, ' '));
        }
    }
