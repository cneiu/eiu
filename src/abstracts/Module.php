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
     * @var string
     */
    private $flash_message = null;
    
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
     * 设置消息
     *
     * @param $message
     *
     * @return bool
     */
    public function setMessage($message)
    {
        return $this->flash_message = $message;
    }
    
    /**
     * 获取消息
     *
     * @return mixed
     */
    public function getMessage()
    {
        $message             = $this->flash_message;
        $this->flash_message = null;
        
        return $message;
    }
}