<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography;


use eiu\components\Component;


/**
 * RSA 加解密
 */
class RSAComponent extends Component
{
    /**
     * RSA 加密
     *
     * @param string $ciphertext
     *
     * @return bool
     */
    public static function encode(string $ciphertext)
    {
        $key = APP_STORAGE . 'rsa' . DS . 'rsa_public_key.pem';
        
        if (!is_file($key))
        {
            throw new \Exception("Openssl public key not found \"{$key}\"");
        }
        
        $key = file_get_contents($key);
        
        if (openssl_public_encrypt($ciphertext, $decrypted, $key))
        {
            return $decrypted;
        }
        
        throw new \Exception("Openssl encrypt failure");
    }
    
    /**
     * RSA 解密
     *
     * @param string $ciphertext
     *
     * @return bool
     */
    public static function decode(string $ciphertext)
    {
        $key = APP_STORAGE . 'rsa' . DS . 'rsa_private_key.pem';
        
        if (!is_file($key))
        {
            throw new \Exception("Openssl private key not found \"{$key}\"");
        }
        
        $key = file_get_contents($key);
        
        if (openssl_private_decrypt($ciphertext, $decrypted, $key))
        {
            return $decrypted;
        }
        
        throw new \Exception("Openssl decrypt failure");
    }
}