<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Signer;

/**
 * A utilitarian class that encapsulates the retrieval of public and private keys
 * *
 *
 * @deprecated Since we've removed OpenSSL from ECDSA there's no reason to use this class
 */
class Keychain
{
    /**
     * Returns a private key from file path or content
     *
     * @param string $key
     * @param string $passphrase
     *
     * @return Key
     */
    public function getPrivateKey($key, $passphrase = null)
    {
        return new Key($key, $passphrase);
    }
    
    /**
     * Returns a public key from file path or content
     *
     * @param string $certificate
     *
     * @return Key
     */
    public function getPublicKey($certificate)
    {
        return new Key($certificate);
    }
}
