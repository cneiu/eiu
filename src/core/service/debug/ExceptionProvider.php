<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\debug;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\output\OutputProvider;
use eiu\core\service\Provider;
use eiu\core\service\router\RouterProvider;
use eiu\core\service\view\ViewProvider;


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
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * 服务启动
     *
     * @param LoggerProvider $logger
     */
    public function boot(LoggerProvider $logger)
    {
        $this->logger = $logger;
        
        error_reporting(-1);
        ini_set('display_errors', 1);
        set_error_handler([&$this, 'errorHandler']);
        set_exception_handler([&$this, 'exceptionHandler']);
    }
    
    /**
     * @inheritdoc
     */
    public function errorHandler(int $severity, string $message, string $file = '', int $line = 0)
    {
        // 获取重要程度说明
        $severityLevel = static::$severityLevels[$severity] ?? 'Unknown';
        
        // 写错误消息日志
        $this->logger->error("{$severityLevel}: {$message} in {$file}:{$line}");
        $this->show(500, $message, $file, $line, []);
    }
    
    /**
     * @inheritdoc
     */
    public function exceptionHandler($e)
    {
        /** @var \Exception $e */
        $status  = $e->getCode();
        $message = $e->getMessage();
        $file    = $e->getFile();
        $line    = $e->getLine();
        $context = $e->getTrace();
        $this->logger->error("{$message} in {$file}:{$line}");
        $this->show($status, $message, $file, $line, $context);
    }
    
    public function show(int $status, string $message, string $file, int $line, array $context = [])
    {
        // 写错误跟踪日志
        if ($context)
        {
            $tracks = 'Tracking:' . PHP_EOL;
            
            foreach ($context as $c)
            {
                if (isset($c['file']))
                {
                    $tracks .= "\t" . 'File: ' . $c['file'] . PHP_EOL;
                }
                
                if (isset($c['line']))
                {
                    $tracks .= "\t" . 'Line: ' . $c['line'] . PHP_EOL;
                }
                
                if (isset($c['class']))
                {
                    $tracks .= "\t" . 'Class: ' . $c['class'] . PHP_EOL;
                }
                
                if (isset($c['function']))
                {
                    $tracks .= "\t" . 'Function: ' . $c['function'] . PHP_EOL;
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
        
        if (isset($_SERVER['HTTP_ACCEPT']) and (false !== stripos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json')))
        {
            if ($config['app']['DEBUG'])
            {
                $json                       = [];
                $json['error']['message']   = $message;
                $json['error']['file']      = $file;
                $json['error']['line']      = $line;
                $json['error']['backtrace'] = $context;
                $view->text(json_encode($json), $status, 'json', 'utf-8');
            }
            else
            {
                $json                     = [];
                $json['error']['message'] = 'Server Error';
                $view->text(json_encode($json), $status, 'json', 'utf-8');
            }
        }
        else
        {
            if ($config['app']['DEBUG'])
            {
                $view_file     = APP_TEMPLATE . 'error' . DS . 'http' . DS . 'debug' . DS . 'error';
                $view->status  = $status;
                $view->message = $message;
                $view->file    = $file;
                $view->line    = $line;
                $view->context = $context;
                $view->router  = $router;
                $view->display($view_file, false, $status);
            }
            else
            {
                $view_file = APP_TEMPLATE . 'error' . DS . 'http' . DS . 'production' . DS . 'error';
                $view->display($view_file, false, $status);
            }
        }
        
        $output->render();
        
        exit;
    }
}