<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\event;


use Closure;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class EventProvider extends Provider
{
    static protected $events = [];
    
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * 服务注册
     */
    public function register()
    {
        $this->app->instance($this->alias(), $this);
        $this->app->instance(__CLASS__, $this);
    }
    
    /**
     * 服务启动
     *
     * @param ConfigProvider        $config
     * @param Logger|LoggerProvider $logger
     */
    public function boot(ConfigProvider $config, LoggerProvider $logger)
    {
        // 加载配置事件
        if ($events = $config['event'])
        {
            foreach ($events as $eventName => $callback)
            {
                $this->bind($eventName, $callback);
            }
        }
        
        $this->logger = $logger;
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * 绑定事件
     *
     * @param string  $eventName 事件名
     * @param Closure $callback  回调函数
     */
    public function bind(string $eventName, Closure $callback)
    {
        if (!isset(self::$events[$eventName]))
        {
            self::$events[$eventName] = [];
        }
        
        if ($index = array_search($callback, self::$events[$eventName]))
        {
            self::$events[$eventName][$index] = $callback;
        }
        else
        {
            self::$events[$eventName][] = $callback;
        }
    }
    
    /**
     * 触发事件
     *
     * @param string $eventName 事件名
     * @param array  $params    事件参数
     */
    public function fire(string $eventName, $params = [])
    {
        if (isset(self::$events[$eventName]) and is_array(self::$events[$eventName]))
        {
            foreach (self::$events[$eventName] as $event)
            {
                if ($event instanceof Closure)
                {
                    $params = array_merge([$this->app], $params);
                    $this->app->call($event, $params);
                    $this->logger->info('Call event ' . $eventName . ' over.');
                }
            }
        }
    }
    
    /**
     * 解除已绑定事件
     *
     * @param string $eventName 事件名
     */
    public function unbind(string $eventName)
    {
        unset(self::$events[$eventName]);
    }
    
    /**
     * 清除所有已绑定事件
     */
    public function flush()
    {
        self::$events = [];
    }
}