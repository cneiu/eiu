<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\encryption\keys;

/**
 * Defines encryption and authentication keys that are derived from a user-supplied password
 */
class DerivedKeys
{
    /** @var string The encryption key */
    private $encryptionKey = '';
    /** @var string The authentication key */
    private $authenticationKey = '';
    
    /**
     * @param string $encryptionKey     The encryption key
     * @param string $authenticationKey The authentication key
     */
    public function __construct(string $encryptionKey, string $authenticationKey)
    {
        $this->encryptionKey     = $encryptionKey;
        $this->authenticationKey = $authenticationKey;
    }
    
    /**
     * @return string
     */
    public function getAuthenticationKey(): string
    {
        return $this->authenticationKey;
    }
    
    /**
     * @return string
     */
    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }
}
