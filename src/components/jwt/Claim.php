<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt;


use JsonSerializable;


/**
 * Basic interface for token claims
 */
interface Claim extends JsonSerializable
{
    /**
     * Returns the claim name
     *
     * @return string
     */
    public function getName();
    
    /**
     * Returns the claim value
     *
     * @return string
     */
    public function getValue();
    
    /**
     * Returns the string representation of the claim
     *
     * @return string
     */
    public function __toString();
}
