<?php

    if(class_exists('ParagonIE_Sodium_Core_XSalsa20', false))
    {
        return;
    }

    /**
     * Class ParagonIE_Sodium_Core_XSalsa20
     */
    abstract class ParagonIE_Sodium_Core_XSalsa20 extends ParagonIE_Sodium_Core_HSalsa20
    {
        /**
         * Encrypt a string with XSalsa20. Doesn't provide integrity.
         *
         * @param string $message
         * @param string $nonce
         * @param string $key
         *
         * @return string
         * @throws SodiumException
         * @throws TypeError
         * @internal You should not use this directly from another application
         *
         */
        public static function xsalsa20_xor($message, $nonce, $key)
        {
            return self::xorStrings($message, self::xsalsa20(self::strlen($message), $nonce, $key));
        }

        /**
         * Expand a key and nonce into an xsalsa20 keystream.
         *
         * @param int    $len
         * @param string $nonce
         * @param string $key
         *
         * @return string
         * @throws SodiumException
         * @throws TypeError
         * @internal You should not use this directly from another application
         *
         */
        public static function xsalsa20($len, $nonce, $key)
        {
            $ret = self::salsa20($len, self::substr($nonce, 16, 8), self::hsalsa20($nonce, $key));

            return $ret;
        }
    }
