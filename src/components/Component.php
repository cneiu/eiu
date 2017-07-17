<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components;


use eiu\core\application\Application;


abstract class Component implements IComponent
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;
    
    /**
     * IModule constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * to string
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__;
    }
    
    /**
     * 类名(不含命名空间)
     *
     * @return string
     */
    public function className()
    {
        return trim(substr(static::class, strripos(static::class, '\\')), '\\');
    }
}