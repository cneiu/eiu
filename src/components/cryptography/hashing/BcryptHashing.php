<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\hashing;

/**
 * Defines the Bcrypt cryptographic hasher
 */
class BcryptHashing extends Hasher
{
    /** The default cost used by this hasher */
    const DEFAULT_COST = 10;
    
    /**
     * @inheritdoc
     */
    public function hash(string $unhashedValue, array $options = [], string $pepper = ''): string
    {
        if (!isset($options['cost']))
        {
            $options['cost'] = self::DEFAULT_COST;
        }
        
        return parent::hash($unhashedValue, $options, $pepper);
    }
    
    /**
     * @inheritdoc
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        if (!isset($options['cost']))
        {
            $options['cost'] = self::DEFAULT_COST;
        }
        
        return parent::needsRehash($hashedValue, $options);
    }
    
    /**
     * @inheritdoc
     */
    protected function setHashAlgorithm()
    {
        $this->hashAlgorithm = PASSWORD_BCRYPT;
    }
}
