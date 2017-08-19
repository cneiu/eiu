<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\application;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\debug\ExceptionProvider;
use eiu\core\service\event\EventProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\output\OutputProvider;
use eiu\core\service\Provider;
use eiu\core\service\router\RequestProvider;
use eiu\core\service\router\RouterProvider;
use eiu\core\service\security\SecurityProvider;
use eiu\core\service\view\ViewProvider;


/**
 * 核心应用
 *
 * @package eiu\core\application
 */
class Application extends Container implements IApplication
{
    /**
     * 框架版本
     *
     * @var string
     */
    const VERSION = '3.0.1';
    
    /**
     * 计时器
     *
     * @var array
     */
    static private $timerTicks = [];
    
    /**
     * 应用服务是否已启动
     *
     * @var bool
     */
    private $booted = false;
    
    /**
     * 服务注册数组
     *
     * @var array
     */
    private $Providers = [];
    
    /**
     * 服务加载标记数组
     *
     * @var array
     */
    private $loadedProviders = [];
    
    /**
     * 构造应用
     */
    public function __construct()
    {
        $this->registerBaseBindings();
        $this->registerBaseProviders();
    }
    
    /**
     * 注册应用
     *
     * @return void
     */
    private function registerBaseBindings()
    {
        static::setInstance($this);
        
        $this->instance('app', $this);
        $this->instance(__CLASS__, $this);
        $this->instance(Container::class, $this);
    }
    
    /**
     * 注册基础服务
     *
     * @return void
     */
    private function registerBaseProviders()
    {
        // 注册配置服务
        $this->register(ConfigProvider::class);
    
        // 注册异常处理服务
        $this->register(ExceptionProvider::class);
        
        // 注册事件服务
        $this->register(EventProvider::class);
        
        // 注册日志服务
        $this->register(LoggerProvider::class);
        
        // 注册安全服务
        $this->register(SecurityProvider::class);
        
        // 注册路由服务
        $this->register(RouterProvider::class);
        
        // 注册请求包装服务
        $this->register(RequestProvider::class);
        
        // 注册渲染输出服务
        $this->register(OutputProvider::class);
        
        // 注册视图服务
        $this->register(ViewProvider::class);
    }
    
    /**
     * 注册服务
     *
     * @param  Provider|string $provider
     * @param  array           $options
     * @param  bool            $force
     *
     * @return Provider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force)
        {
            return $registered;
        }
        
        if (is_string($provider))
        {
            $provider = $this->resolveProvider($provider);
        }
        
        if (method_exists($provider, 'register'))
        {
            $provider->register();
        }
        
        $this->markAsRegistered($provider);
        
        if ($this->booted)
        {
            $this->bootProvider($provider);
        }
        
        return $provider;
    }
    
    /**
     * 获取存在的服务
     *
     * @param  Provider|string $provider
     *
     * @return Provider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        
        
        foreach ($this->Providers as $key => $value)
        {
            if (call_user_func(
                function ($value) use ($name) {
                    return $value instanceof $name;
                }, $value, $key
            ))
            {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * 实例化服务
     *
     * @param  string $provider
     *
     * @return Provider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }
    
    /**
     * 标记服务已注册
     *
     * @param  Provider $provider
     *
     * @return void
     */
    private function markAsRegistered($provider)
    {
        $this->Providers[] = $provider;
        
        $this->loadedProviders[get_class($provider)] = true;
    }
    
    /**
     * 启动服务引导
     *
     * @param Provider $provider
     *
     * @return mixed
     */
    private function bootProvider(Provider $provider)
    {
        if (method_exists($provider, 'boot'))
        {
            return $this->call([$provider, 'boot']);
        }
        
        return null;
    }
    
    /**
     * 服务是否已启动
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }
    
    /**
     * 启动服务引导
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted)
        {
            return;
        }
        
        array_walk(
            $this->Providers, function ($p) {
            $this->bootProvider($p);
        }
        );
        
        $this->booted = true;
    }
    
    /**
     * 获取已加载服务
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }
    
    /**
     * 记录时间点
     *
     * 记录一个时间点用于计算片段运行时间
     *
     * @param string $tick 时间点名称
     *
     * @param null   $time 毫秒级时间戳
     */
    public function timerTick(string $tick, $time = null)
    {
        self::$timerTicks[$tick] = $time ? $time : microtime(true);
    }
    
    /**
     * 是否已记录时间点
     *
     * @param string $tick 时间点名称
     *
     * @return bool
     */
    public function timerIsTick(string $tick)
    {
        return isset(self::$timerTicks[$tick]);
    }
    
    /**
     * 删除已记录时间点
     *
     * @param string $tick 时间点名称
     */
    public function timerUnsetTick(string $tick)
    {
        unset(self::$timerTicks[$tick]);
    }
    
    /**
     * 计算时间片段
     *
     * 计算两个时间点之间的时长
     *
     * @param string  $tick1    起始时间点名称
     * @param string  $tick2    结束时间点名称
     * @param integer $decimals 保留位数
     *
     * @return string
     */
    public function timerElapsed(string $tick1, string $tick2 = null, int $decimals = 4)
    {
        if (!isset(self::$timerTicks[$tick1]))
        {
            return 0;
        }
        
        $tick2 = (!$tick2 or !isset(self::$timerTicks[$tick2])) ? microtime(true) : self::$timerTicks[$tick2];
        
        return number_format(self::$timerTicks[$tick1] - $tick2, $decimals);
    }
    
    /**
     * 获取内存消耗值
     *
     * 根据值自动换算大小单位 bytes kb mb
     *
     * @return string
     */
    public function getMemory()
    {
        if (!function_exists('memory_get_usage'))
        {
            return 0;
        }
        
        $mem_usage = memory_get_usage(true);
        
        if ($mem_usage < 1024)
        {
            $mem = $mem_usage . ' bytes';
        }
        else if ($mem_usage < 1048576)
        {
            $mem = round($mem_usage / 1024, 2) . ' kb';
        }
        else
        {
            $mem = round($mem_usage / 1048576, 2) . ' mb';
        }
        
        return $mem;
    }
    
    /**
     * 输出框架信息
     *
     * @return string
     */
    public function __toString()
    {
        return 'EIU ' . $this->version();
    }
    
    /**
     * 获取版本
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }
}