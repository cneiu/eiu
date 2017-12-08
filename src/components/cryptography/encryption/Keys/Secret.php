<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\encryption\keys;

/**
 * Defines a cryptographic secret
 */
class Secret
{
    /** @var string The type of secret this is */
    private $type = '';
    /** @var string The secret value */
    private $value = '';
    
    /**
     * @param string $type  The type of secret this is
     * @param string $value The secret value
     */
    public function __construct(string $type, string $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
