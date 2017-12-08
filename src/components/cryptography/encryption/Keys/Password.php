<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\encryption\keys;

/**
 * Defines a cryptographic password
 */
class Password extends Secret
{
    /**
     * @param string $value The secret password
     */
    public function __construct(string $value)
    {
        parent::__construct(SecretTypes::PASSWORD, $value);
    }
}
