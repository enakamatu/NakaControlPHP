<?php
class SecurityHelper
{
    private static $cipher = 'AES-256-CBC';
    // Em produção, defina a chave no arquivo app/config/application.ini ou variável de ambiente
    private static $key = 'SUA_CHAVE_SECRETA_DE_32_BYTES_AQUI!';

    public static function encrypt($plainText)
    {
        if (empty($plainText)) {
            return null;
        }
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertextRaw = openssl_encrypt($plainText, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertextRaw);
    }

    public static function decrypt($cipherTextBase64)
    {
        if (empty($cipherTextBase64)) {
            return null;
        }
        $raw = base64_decode($cipherTextBase64);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($raw, 0, $ivlen);
        $ciphertextRaw = substr($raw, $ivlen);
        return openssl_decrypt($ciphertextRaw, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
    }
}