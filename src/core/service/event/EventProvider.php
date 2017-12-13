<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\event;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class EventProvider extends Provider
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * @var array
     */
    static protected $events = [];
    
    /**
     * 服务启动
     *
     * @param ConfigProvider $config
     * @param LoggerProvider $logger
     */
    public function boot(ConfigProvider $config, LoggerProvider $logger)
    {
        $this->logger = $logger;
        
        // 加载配置事件
        if ($events = $config['event'])
        {
            foreach ($events as $eventName => $callbacks)
            {
                if (is_array($callbacks) and $callbacks)
                {
                    self::$events[$eventName] = $callbacks;
                }
            }
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
            $this->logger->info('Emit event: ' . $eventName);
            
            foreach (self::$events[$eventName] as $event)
            {
                $this->app->call($event, $params);
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