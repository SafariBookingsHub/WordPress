<?php

    if(! is_callable('sodium_crypto_stream_xchacha20'))
    {
        /**
         * @param int    $len
         * @param string $nonce
         * @param string $key
         *
         * @return string
         * @throws SodiumException
         * @throws TypeError
         * @see ParagonIE_Sodium_Compat::crypto_stream_xchacha20()
         */
        function sodium_crypto_stream_xchacha20($len, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xchacha20($len, $nonce, $key, true);
        }
    }
    if(! is_callable('sodium_crypto_stream_xchacha20_keygen'))
    {
        /**
         * @return string
         * @throws Exception
         * @see ParagonIE_Sodium_Compat::crypto_stream_xchacha20_keygen()
         */
        function sodium_crypto_stream_xchacha20_keygen()
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xchacha20_keygen();
        }
    }
    if(! is_callable('sodium_crypto_stream_xchacha20_xor'))
    {
        /**
         * @param string $message
         * @param string $nonce
         * @param string $key
         *
         * @return string
         * @throws SodiumException
         * @throws TypeError
         * @see ParagonIE_Sodium_Compat::crypto_stream_xchacha20_xor()
         */
        function sodium_crypto_stream_xchacha20_xor($message, $nonce, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xchacha20_xor($message, $nonce, $key, true);
        }
    }
    if(! is_callable('sodium_crypto_stream_xchacha20_xor_ic'))
    {
        /**
         * @param string $message
         * @param string $nonce
         * @param int    $counter
         * @param string $key
         *
         * @return string
         * @throws SodiumException
         * @throws TypeError
         * @see ParagonIE_Sodium_Compat::crypto_stream_xchacha20_xor_ic()
         */
        function sodium_crypto_stream_xchacha20_xor_ic($message, $nonce, $counter, $key)
        {
            return ParagonIE_Sodium_Compat::crypto_stream_xchacha20_xor_ic($message, $nonce, $counter, $key, true);
        }
    }
