<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Signer;


use eiu\components\jwt\Signature;
use eiu\components\jwt\Signer;


/**
 * Base class for signers
 */
abstract class BaseSigner implements Signer
{
    /**
     * {@inheritdoc}
     */
    public function modifyHeader(array &$headers)
    {
        $headers['alg'] = $this->getAlgorithmId();
    }
    
    /**
     * {@inheritdoc}
     */
    public function sign($payload, $key)
    {
        return new Signature($this->createHash($payload, $this->getKey($key)));
    }
    
    /**
     * Creates a hash with the given data
     *
     * @param string $payload
     * @param Key    $key
     *
     * @return string
     */
    abstract public function createHash($payload, Key $key);
    
    /**
     * @param Key|string $key
     *
     * @return Key
     */
    private function getKey($key)
    {
        if (is_string($key))
        {
            $key = new Key($key);
        }
        
        return $key;
    }
    
    /**
     * {@inheritdoc}
     */
    public function verify($expected, $payload, $key)
    {
        return $this->doVerify($expected, $payload, $this->getKey($key));
    }
    
    /**
     * Creates a hash with the given data
     *
     * @param string $payload
     * @param Key    $key
     *
     * @return boolean
     */
    abstract public function doVerify($expected, $payload, Key $key);
}
