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
     * 别名
     *
     * @return string
     */
    public function alias()
    {
        return strtolower(str_replace('Provider', '', trim(substr(static::class, strripos(static::class, '\\')), '\\')));
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