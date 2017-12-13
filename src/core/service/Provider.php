<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service;


use eiu\core\application\Application;


abstract class Provider
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;
    
    /**
     * Provider constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * 服务提供者注册
     *
     * @return mixed
     */
    public function register()
    {
        $alias = strtolower(str_replace('Provider', '', trim(substr(static::class, strripos(static::class, '\\')), '\\')));
        $this->app->instance(get_called_class(), $this);
        $this->app->instance($alias, $this);
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
}