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
 * Signer for ECDSA SHA-384
 * */
class Sha384 extends Ecdsa
{
    /**
     * {@inheritdoc}
     */
    public function getAlgorithmId()
    {
        return 'ES384';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAlgorithm()
    {
        return 'sha384';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSignatureLength()
    {
        return 96;
    }
}
