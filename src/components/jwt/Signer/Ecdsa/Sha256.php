<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Signer\Ecdsa;


use eiu\components\jwt\Signer\Ecdsa;


/**
 * Signer for ECDSA SHA-256
 * */
class Sha256 extends Ecdsa
{
    /**
     * {@inheritdoc}
     */
    public function getAlgorithmId()
    {
        return 'ES256';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAlgorithm()
    {
        return 'sha256';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSignatureLength()
    {
        return 64;
    }
}
