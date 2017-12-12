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
 * Class that create claims
 */
class Factory
{
    /**
     * The list of claim callbacks
     *
     * @var array
     */
    private $callbacks;
    
    /**
     * Initializes the factory, registering the default callbacks
     *
     * @param array $callbacks
     */
    public function __construct(array $callbacks = [])
    {
        $this->callbacks = array_merge(
            [
                'iat' => [$this, 'createLesserOrEqualsTo'],
                'nbf' => [$this, 'createLesserOrEqualsTo'],
                'exp' => [$this, 'createGreaterOrEqualsTo'],
                'iss' => [$this, 'createEqualsTo'],
                'aud' => [$this, 'createEqualsTo'],
                'sub' => [$this, 'createEqualsTo'],
                'jti' => [$this, 'createEqualsTo'],
            ],
            $callbacks
        );
    }
    
    /**
     * Create a new claim
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Claim
     */
    public function create($name, $value)
    {
        if (!empty($this->callbacks[$name]))
        {
            return call_user_func($this->callbacks[$name], $name, $value);
        }
        
        return $this->createBasic($name, $value);
    }
    
    /**
     * Creates a basic claim
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Basic
     */
    private function createBasic($name, $value)
    {
        return new Basic($name, $value);
    }
    
    /**
     * Creates a claim that can be compared (greator or equals)
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return GreaterOrEqualsTo
     */
    private function createGreaterOrEqualsTo($name, $value)
    {
        return new GreaterOrEqualsTo($name, $value);
    }
    
    /**
     * Creates a claim that can be compared (greator or equals)
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return LesserOrEqualsTo
     */
    private function createLesserOrEqualsTo($name, $value)
    {
        return new LesserOrEqualsTo($name, $value);
    }
    
    /**
     * Creates a claim that can be compared (equals)
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return EqualsTo
     */
    private function createEqualsTo($name, $value)
    {
        return new EqualsTo($name, $value);
    }
}
