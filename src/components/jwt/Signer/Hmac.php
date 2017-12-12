<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Signer;

/**
 * Base class for hmac signers
 */
abstract class Hmac extends BaseSigner
{
    /**
     * {@inheritdoc}
     */
    public function doVerify($expected, $payload, Key $key)
    {
        if (!is_string($expected))
        {
            return false;
        }
        
        $callback = function_exists('hash_equals') ? 'hash_equals' : [$this, 'hashEquals'];
        
        return call_user_func($callback, $expected, $this->createHash($payload, $key));
    }
    
    /**
     * {@inheritdoc}
     */
    public function createHash($payload, Key $key)
    {
        return hash_hmac($this->getAlgorithm(), $payload, $key->getContent(), true);
    }
    
    /**
     * Returns the algorithm name
     *
     * @return string
     */
    abstract public function getAlgorithm();
    
    /**
     * PHP < 5.6 timing attack safe hash comparison
     *
     * @param string $expected
     * @param string $generated
     *
     * @return boolean
     */
    public function hashEquals($expected, $generated)
    {
        $expectedLength = strlen($expected);
        
        if ($expectedLength !== strlen($generated))
        {
            return false;
        }
        
        $res = 0;
        
        for ($i = 0; $i < $expectedLength; ++$i)
        {
            $res |= ord($expected[$i]) ^ ord($generated[$i]);
        }
        
        return $res === 0;
    }
}
