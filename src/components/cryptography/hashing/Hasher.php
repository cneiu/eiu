<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\hashing;


/**
 * Defines a base cryptographic hasher
 */
abstract class Hasher implements IHasher
{
    /** @var int The hash algorithm constant used by this hasher */
    protected $hashAlgorithm = -1;
    
    public function __construct()
    {
        $this->setHashAlgorithm();
    }
    
    /**
     * Should set the hash algorithm property to the algorithm used by the concrete class
     */
    abstract protected function setHashAlgorithm();
    
    /**
     * @inheritdoc
     */
    public static function verify(string $hashedValue, string $unhashedValue, string $pepper = ''): bool
    {
        return \password_verify($unhashedValue . $pepper, $hashedValue);
    }
    
    /**
     * @inheritdoc
     */
    public function hash(string $unhashedValue, array $options = [], string $pepper = ''): string
    {
        $hashedValue = \password_hash($unhashedValue . $pepper, $this->hashAlgorithm, $options);
        
        if ($hashedValue === false)
        {
            throw new HashingException("Failed to generate hash for algorithm {$this->hashAlgorithm}");
        }
        
        return $hashedValue;
    }
    
    /**
     * @inheritdoc
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return \password_needs_rehash($hashedValue, $this->hashAlgorithm, $options);
    }
}
