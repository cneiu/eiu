<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\abstracts;


use eiu\core\application\Application;


abstract class Module
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
        
        if (!$this->app->bound('message'))
        {
            $this->app->instance('message', null);
        }
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
     * make an object use Container
     *
     * @param $abstract
     *
     * @return mixed
     */
    public function make($abstract)
    {
        return $this->app->make($abstract);
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
    
    /**
     * 设置消息
     *
     * @param $message
     *
     * @return bool
     */
    public function setMessage($message)
    {
        $this->app->instance('message', $message);
        
        return false;
    }
    
    /**
     * 获取消息
     *
     * @return mixed
     */
    public function getMessage()
    {
        if (!isset($this->app['message']))
        {
            return null;
        }
        
        $message = $this->app['message'];
        unset($this->app['message']);
        
        return $message;
    }
}