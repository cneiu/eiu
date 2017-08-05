<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\debug;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\output\OutputProvider;
use eiu\core\service\Provider;
use eiu\core\service\router\RouterProvider;
use eiu\core\service\view\ViewProvider;
use Exception;


class ExceptionProvider extends Provider
{
    private static $severityLevels = [
        E_ERROR           => 'Error',
        E_WARNING         => 'Warning',
        E_PARSE           => 'Parsing Error',
        E_NOTICE          => 'Notice',
        E_CORE_ERROR      => 'Core Error',
        E_CORE_WARNING    => 'Core Warning',
        E_COMPILE_ERROR   => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR      => 'User Error',
        E_USER_WARNING    => 'User Warning',
        E_USER_NOTICE     => 'User Notice',
        E_STRICT          => 'Runtime Notice',
    
    ];
    
    /**
     *  日志对象
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * 获取错误重要程度说明
     *
     * @param int $code
     *
     * @return mixed|string
     */
    private static function getSeverityLevels(int $code)
    {
        return static::$severityLevels[$code] ?? 'Unknown';
    }
    
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
     * @param Logger|LoggerProvider $logger
     */
    public function boot(LoggerProvider $logger)
    {
        $this->logger = $logger;
        
        error_reporting(-1);
        ini_set('display_errors', 0);
        set_error_handler([&$this, 'errorHandler']);
        set_exception_handler([&$this, 'exceptionHandler']);
        
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * @inheritdoc
     */
    public function errorHandler(int $severity, string $message, string $file = '', int $line = 0)
    {
        $this->showError($severity, $message, $file, $line);
    }
    
    /**
     * @inheritdoc
     */
    public function exceptionHandler(Exception $e)
    {
        $this->showException($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
    }
    
    /**
     * 输出错误
     *
     * @param int         $severity 重要程度
     * @param string      $message  消息
     * @param string|null $file     文件
     * @param int|null    $line     行
     */
    public function showError(int $severity, string $message, string $file = null, int $line = null)
    {
        // 获取重要程度说明
        $severityLevel = self::getSeverityLevels($severity);
        
        // 写错误消息日志
        $this->logger->error("{$severityLevel}: {$message} in {$file}:{$line}");
        
        // 写错误跟踪日志
        $context = [];
        
        if (function_exists('debug_backtrace') and $context = debug_backtrace())
        {
            $tracks = 'Tracking:' . PHP_EOL;
            
            foreach ($context as $c)
            {
                if (isset($c['file']))
                {
                    $tracks .= 'File: ' . $c['file'] . PHP_EOL;
                }
                
                if (isset($c['line']))
                {
                    $tracks .= 'Line: ' . $c['line'] . PHP_EOL;
                }
                
                if (isset($c['class']))
                {
                    $tracks .= 'Class: ' . $c['class'] . PHP_EOL;
                }
                
                if (isset($c['function']))
                {
                    $tracks .= 'Function: ' . $c['function'] . PHP_EOL;
                }
            }
            
            $this->logger->error($tracks);
        }
        
        /** @var ConfigProvider $config */
        $config = $this->app->make(ConfigProvider::class);
        
        /** @var ViewProvider $view */
        $view = $this->app->make(ViewProvider::class);
        
        /** @var OutputProvider $output */
        $output = $this->app->make(OutputProvider::class);
        
        // 清空输出缓冲区
        if (ob_get_level() > ob_get_level() + 1)
        {
            ob_end_flush();
        }
        
        // 执行模式
        if ((PHP_SAPI === 'cli' or defined('STDIN')))
        {
            $view->text($message, 500);
            $output->render();
        }
        else
        {
            // 默认开发模式
            $mode = 'production';
            
            if (isset($config['app']['RUN_MODE']))
            {
                $mode = $config['app']['RUN_MODE'];
            }
            
            $view_file = $view_file = 'error' . DS . 'html' . DS . $mode . DS . 'error';
            
            if ($view->exist($view_file))
            {
                $view->severityLevel = $severityLevel;
                $view->status        = 500;
                $view->message       = $message;
                $view->file          = $file;
                $view->line          = $line;
                $view->context       = $context;
                $view->display($view_file, false, 500);
            }
            else
            {
                $view->text($message, 500);
            }
            
            $output->render();
        }
        
        exit;
    }
    
    /**
     * 输出异常
     *
     * @param int    $status
     * @param string $message
     * @param string $file
     * @param int    $line
     * @param array  $context
     */
    public function showException(int $status, string $message, string $file, int $line, array $context = [])
    {
        // 写错误消息日志
        $this->logger->error("{$message} in {$file}:{$line}");
        
        // 写错误跟踪日志
        if ($context)
        {
            $tracks = 'Tracking:' . PHP_EOL;
            
            foreach ($context as $c)
            {
                if (isset($c['file']))
                {
                    $tracks .= 'File: ' . $c['file'] . PHP_EOL;
                }
                
                if (isset($c['line']))
                {
                    $tracks .= 'Line: ' . $c['line'] . PHP_EOL;
                }
                
                if (isset($c['class']))
                {
                    $tracks .= 'Class: ' . $c['class'] . PHP_EOL;
                }
                
                if (isset($c['function']))
                {
                    $tracks .= 'Function: ' . $c['function'] . PHP_EOL;
                }
            }
            
            $this->logger->error($tracks);
        }
        
        /** @var ConfigProvider $config */
        $config = $this->app->make(ConfigProvider::class);
        
        /** @var ViewProvider $view */
        $view = $this->app->make(ViewProvider::class);
        
        /** @var OutputProvider $output */
        $output = $this->app->make(OutputProvider::class);
        
        /** @var RouterProvider $router */
        $router = $this->app->make(RouterProvider::class);
        
        // 清空输出缓冲区
        if (ob_get_level() > ob_get_level() + 1)
        {
            ob_end_flush();
        }
        
        // 执行模式
        if ((PHP_SAPI === 'cli' or defined('STDIN')))
        {
            $view->text($message, $status);
            $output->render();
        }
        else
        {
            // 默认开发模式
            $mode = 'production';
            
            if (isset($config['app']['RUN_MODE']))
            {
                $mode = $config['app']['RUN_MODE'];
            }
            
            $view_file = $view_file = 'error' . DS . 'html' . DS . $mode . DS . $status;
            
            if ($view->exist($view_file))
            {
                $view->status  = $status;
                $view->message = $message;
                $view->file    = $file;
                $view->line    = $line;
                $view->context = $context;
                $view->router  = $router;
                $view->display($view_file, $status);
            }
            else
            {
                $view->text($message, $status);
            }
            
            $output->render();
        }
        
        exit;
    }
}