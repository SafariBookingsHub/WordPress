<?php
    /**
     * Error Protection API: WP_Paused_Extensions_Storage class
     *
     * @package WordPress
     * @since   5.2.0
     */

    /**
     * Core class used for storing paused extensions.
     *
     * @since 5.2.0
     */
    #[AllowDynamicProperties]
    class WP_Paused_Extensions_Storage
    {
        /**
         * Type of extension. Used to key extension storage.
         *
         * @since 5.2.0
         * @var string
         */
        protected $type;

        /**
         * Constructor.
         *
         * @param string $extension_type Extension type. Either 'plugin' or 'theme'.
         *
         * @since 5.2.0
         *
         */
        public function __construct($extension_type)
        {
            $this->type = $extension_type;
        }

        /**
         * Records an extension error.
         *
         * Only one error is stored per extension, with subsequent errors for the same extension overriding the
         * previously stored error.
         *
         * @param string $extension Plugin or theme directory name.
         * @param array  $error     {
         *                          Error information returned by `error_get_last()`.
         *
         * @type int     $type      The error type.
         * @type string  $file      The name of the file in which the error occurred.
         * @type int     $line      The line number in which the error occurred.
         * @type string  $message   The error message.
         *                          }
         * @return bool True on success, false on failure.
         * @since 5.2.0
         *
         */
        public function set($extension, $error)
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            // Do not update if the error is already stored.
            if(isset($paused_extensions[$this->type][$extension]) && $paused_extensions[$this->type][$extension] === $error)
            {
                return true;
            }

            $paused_extensions[$this->type][$extension] = $error;

            return update_option($option_name, $paused_extensions);
        }

        /**
         * Checks whether the underlying API to store paused extensions is loaded.
         *
         * @return bool True if the API is loaded, false otherwise.
         * @since 5.2.0
         *
         */
        protected function is_api_loaded()
        {
            return function_exists('get_option');
        }

        /**
         * Get the option name for storing paused extensions.
         *
         * @return string
         * @since 5.2.0
         *
         */
        protected function get_option_name()
        {
            if(! wp_recovery_mode()->is_active())
            {
                return '';
            }

            $session_id = wp_recovery_mode()->get_session_id();
            if(empty($session_id))
            {
                return '';
            }

            return "{$session_id}_paused_extensions";
        }

        /**
         * Forgets a previously recorded extension error.
         *
         * @param string $extension Plugin or theme directory name.
         *
         * @return bool True on success, false on failure.
         * @since 5.2.0
         *
         */
        public function delete($extension)
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            // Do not delete if no error is stored.
            if(! isset($paused_extensions[$this->type][$extension]))
            {
                return true;
            }

            unset($paused_extensions[$this->type][$extension]);

            if(empty($paused_extensions[$this->type]))
            {
                unset($paused_extensions[$this->type]);
            }

            // Clean up the entire option if we're removing the only error.
            if(! $paused_extensions)
            {
                return delete_option($option_name);
            }

            return update_option($option_name, $paused_extensions);
        }

        /**
         * Gets the error for an extension, if paused.
         *
         * @param string $extension Plugin or theme directory name.
         *
         * @return array|null Error that is stored, or null if the extension is not paused.
         * @since 5.2.0
         *
         */
        public function get($extension)
        {
            if(! $this->is_api_loaded())
            {
                return null;
            }

            $paused_extensions = $this->get_all();

            if(! isset($paused_extensions[$extension]))
            {
                return null;
            }

            return $paused_extensions[$extension];
        }

        /**
         * Gets the paused extensions with their errors.
         *
         * @return array {
         *     Associative array of errors keyed by extension slug.
         *
         * @type array ...$0 Error information returned by `error_get_last()`.
         * }
         * @since 5.2.0
         *
         */
        public function get_all()
        {
            if(! $this->is_api_loaded())
            {
                return [];
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return [];
            }

            $paused_extensions = (array) get_option($option_name, []);

            return isset($paused_extensions[$this->type]) ? $paused_extensions[$this->type] : [];
        }

        /**
         * Remove all paused extensions.
         *
         * @return bool
         * @since 5.2.0
         *
         */
        public function delete_all()
        {
            if(! $this->is_api_loaded())
            {
                return false;
            }

            $option_name = $this->get_option_name();

            if(! $option_name)
            {
                return false;
            }

            $paused_extensions = (array) get_option($option_name, []);

            unset($paused_extensions[$this->type]);

            if(! $paused_extensions)
            {
                return delete_option($option_name);
            }

            return update_option($option_name, $paused_extensions);
        }
    }
