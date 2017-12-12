<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Claim;


use eiu\components\jwt\Claim;


/**
 * The default claim
 */
class Basic implements Claim
{
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var mixed
     */
    private $value;
    
    /**
     * Initializes the claim
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __construct($name, $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
