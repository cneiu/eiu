<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\debug;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\debug\http\ExceptionRenderer;
use eiu\core\service\event\EventProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\output\OutputProvider;
use eiu\core\service\Provider;
use eiu\core\service\view\ViewProvider;
use Exception;


class ExceptionProvider extends Provider
{
    /**
     *  日志对象
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * @var ExceptionRenderer
     */
    private $exceptionRenderer;
    
    /**
     * @var OutputProvider
     */
    private $output;
    
    /**
     * @var ConfigProvider
     */
    private $config;
    
    /**
     * @var ViewProvider
     */
    private $view;
    
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
     * @param ExceptionRenderer     $exceptionRenderer
     * @param OutputProvider        $output
     * @param ConfigProvider        $config
     * @param ViewProvider          $view
     */
    public function boot(LoggerProvider $logger, ExceptionRenderer $exceptionRenderer, OutputProvider $output,
                         ConfigProvider $config, ViewProvider $view)
    {
        $this->logger            = $logger;
        $this->exceptionRenderer = $exceptionRenderer;
        $this->output            = $output;
        $this->config            = $config;
        $this->view              = $view;
        
        error_reporting(-1);
        ini_set('display_errors', 1);
        set_error_handler([&$this, 'errorHandler']);
        set_exception_handler([&$this, 'exceptionHandler']);
        
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * @inheritdoc
     */
    public function errorHandler(int $severity, string $message, string $file = '', int $line = 0, array $context = [])
    {
        if ($is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity))
        {
            if (strpos(PHP_SAPI, 'cgi') === 0)
            {
                header('Status: ' . 500 . ' ' . 500, true);
            }
            else
            {
                header(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1' . ' ' . 500 . ' ' . 'Internal Server Error', true, 500);
            }
        }
        
        if (($severity & error_reporting()) !== $severity)
        {
            return;
        }
        
        $this->logger->error($message, $context);
        
        // Should we display the error?
        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors')))
        {
            $this->showError($severity, $message, $file, $line);
        }
        
        $is_error and exit(1);
    }
    
    /**
     * @inheritdoc
     */
    public function exceptionHandler(Exception $ex)
    {
        $this->logger->error($ex->getMessage(), $ex->getTrace());
        
        if (!(PHP_SAPI === 'cli' or defined('STDIN')))
        {
            if (strpos(PHP_SAPI, 'cgi') === 0)
            {
                header('Status: ' . 500 . ' ' . 500, true);
            }
            else
            {
                header(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1' . ' ' . 500 . ' ' . 'Internal Server Error', true, 500);
            }
        }
        
        // Should we display the error?
        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors')))
        {
            $this->showException($ex);
        }
        
        exit(1);
    }
    
    /**
     * 输出 PHP 错误
     *
     * @param    int    $severity 类型
     * @param    string $message  消息
     * @param    string $filePath 路径
     * @param    int    $line     行
     *
     * @return    string
     */
    public function showError(int $severity, string $message, string $filePath, int $line)
    {
        $path = $this->config['app']['ERROR_VIEWS_PATH'] . DS;
        $path .= (PHP_SAPI === 'cli' or defined('STDIN')) ? 'cli' . DS : 'html' . DS;
        $path .= 'error_php';
        
        if (ob_get_level() > ob_get_level() + 1)
        {
            ob_end_flush();
        }
        
        if ($this->view->exist($path))
        {
            $this->view->severity = $severity;
            $this->view->message  = $message;
            $this->view->filepath = $filePath;
            $this->view->line     = $line;
            $this->view->display($path, false, 500);
        }
        else
        {
            $this->logger->error("Exception template not found in: $path");
            $this->view->text("$message in $filePath: $line", 500);
        }
        
        exit(1);
    }
    
    /**
     * 输出异常
     *
     * @param Exception $exception 异常对象
     */
    public function showException(Exception $exception)
    {
        $path = $this->config['app']['ERROR_VIEWS_PATH'] . DS;
        $path .= (PHP_SAPI === 'cli' or defined('STDIN')) ? 'cli' . DS : 'html' . DS;
        $path .= 'error_exception';
        
        if (ob_get_level() > ob_get_level() + 1)
        {
            ob_end_flush();
        }
        
        if ($this->view->exist($path))
        {
            $this->view->exception = $exception;
            $this->view->message   = $exception->getMessage();
            $this->view->display($path, false, 500);
        }
        else
        {
            $this->logger->error("Exception template not found in: $path");
            $this->view->text($exception->getMessage() . " in " . $exception->getFile() . ": " . $exception->getLine(), 500);
        }
        
        exit(1);
    }
}