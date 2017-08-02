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
use ErrorException;
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
        $exception = new ErrorException($message, $severity, 1, $file, $line);
        $exception->context = $context;
        static::exceptionHandler($exception);
    }
    
    /**
     * @inheritdoc
     */
    public function exceptionHandler(Exception $e)
    {
        $this->logger->error($e->getMessage());
    
        $path = $this->config['app']['ERROR_VIEWS_PATH'] . DS;
        $path .= (PHP_SAPI === 'cli' or defined('STDIN')) ? 'cli' . DS : 'html' . DS;
        $path .= 'error_exception';
    
        if (ob_get_level() > ob_get_level() + 1)
        {
            ob_end_flush();
        }
    
        if ($this->view->exist($path))
        {
            $this->view->exception = $e;
            $this->view->message   = $e->getMessage();
            $this->view->display($path, false, 500);
        }
        else
        {
            $this->logger->error("Exception template not found in: $path");
            $this->view->text($e->getMessage() . " in " . $e->getFile() . ": " . $e->getLine(), 500);
        }
    
        $this->output->render();
        exit;
    }
    
    /**
     * 输出 PHP 错误
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     *
     * @return string
     *
     */
    public function showError(int $errno ,string $errstr ,string $errfile, int $errline)
    {
        $content = sprintf('%s. File: %s (line: %s)', $errstr, $errfile, $errno);
        $exception = new ErrorException($content, $errno, 1, $errfile, $errline);
        static::exceptionHandler($exception);
    }
}