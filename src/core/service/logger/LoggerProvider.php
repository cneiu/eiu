<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\logger;


use eiu\components\files\FilesComponent;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\Provider;


/**
 * Class LoggerProvider
 *
 * @package eiu\core\service\logger
 */
class LoggerProvider extends Provider
{
    
    /**
     * 日志对象
     *
     * @var Logger
     */
    static protected $logger = null;
    /**
     * 日志队列
     *
     * @var array
     */
    static protected $queues = [];
    /**
     * @var ConfigProvider
     */
    private $config;
    /**
     * 日志级别
     *
     * @var array
     */
    private $levels = [
        'EMERGENCY' => 0,
        'ALERT'     => 1,
        'CRITICA'   => 2,
        'ERROR'     => 3,
        'WARNING'   => 4,
        'NOTICE'    => 5,
        'INFO'      => 6,
        'DEBUG'     => 7,
    ];
    
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
     * @param ConfigProvider $config
     * @param FilesComponent $filesComponent
     *
     * @throws \Exception
     */
    public function boot(ConfigProvider $config, FilesComponent $filesComponent)
    {
        $this->config = $config;
        
        if (is_null(static::$logger))
        {
            $path = $this->config['app']['LOG_PATH'];
            $path = DS == substr($path, -1) ? $path : $path . DS;
            $path .= date('Y-m-d') . $this->config['app']['LOG_FILE_EXTENSION'];
            
            if (!$filesComponent->exists(dirname($path)))
            {
                if (!$filesComponent->makeDirectory(dirname($path), 0755, true))
                {
                    throw new \Exception('The log directory cannot be written.', 500);
                }
            }
            
            $logger = new Logger(new writer\File($path));
            $logger->setTimestampFormat($this->config['app']['LOG_DATE_FORMAT']);
            
            static::$logger = $logger;
        }
        
        $this->info($this->className() . " is booted");
    }
    
    /**
     * 外部直接调用日志对象
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        static::$queues[] = compact('name', 'arguments');
        
        if (!is_null(static::$logger))
        {
            foreach (static::$queues as $index => $queue)
            {
                $name = $queue['name'];
                
                if (isset($this->levels[strtoupper($name)]) and in_array($this->levels[strtoupper($name)], $this->config['app']['LOG_THRESHOLD']))
                {
                    static::$logger->$name(...$queue['arguments']);
                }
                
                unset(static::$queues[$index]);
            }
        }
    }
}