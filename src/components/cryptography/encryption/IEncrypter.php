<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\encryption;


use eiu\components\cryptography\encryption\keys\Secret;


/**
 * Defines the interface for encrypters to implement
 */
interface IEncrypter
{
    /**
     * Decrypts the data
     *
     * @param string $data The data to decrypt
     *
     * @return string The decrypted data
     * @throws EncryptionException Thrown if there was an error decrypting the data
     */
    public function decrypt(string $data): string;
    
    /**
     * Encrypts the data
     *
     * @param string $data The data to encrypt
     *
     * @return string The encrypted data
     * @throws EncryptionException Thrown if there was an error encrypting the data
     */
    public function encrypt(string $data): string;
    
    /**
     * Sets the encryption secret that will be used to derive keys
     *
     * @param Secret $secret The secret to use
     */
    public function setSecret(Secret $secret);
}
