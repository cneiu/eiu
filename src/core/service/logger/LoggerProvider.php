<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\logger;


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
     * Constants for log levels
     *
     * @var int
     */
    const EMERGENCY = 0;
    const ALERT     = 1;
    const CRITICAL  = 2;
    const ERROR     = 3;
    const WARNING   = 4;
    const NOTICE    = 5;
    const INFO      = 6;
    const DEBUG     = 7;
    
    /**
     * 开始序号
     *
     * @var int
     */
    private static $startNumber = 0;
    
    /**
     * 开始时间
     *
     * @var int
     */
    private $startTimer = 0;
    
    /**
     * 日志适配器未初始化时的临时缓冲区
     *
     * @var array
     */
    private $buffer = [];
    
    /**
     * Message level short codes
     *
     * @var array
     */
    protected $levels = [
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFO',
        7 => 'DEBUG',
    ];
    
    /**
     * Log writers
     *
     * @var array
     */
    protected $writers = [];
    
    /**
     * Log timestamp format
     *
     * @var string
     */
    protected $timestampFormat = 'Y-m-d H:i:s';
    
    /**
     * 服务启动
     *
     * @param ConfigProvider $config
     *
     * @throws \Exception
     */
    public function boot(ConfigProvider $config)
    {
        $this->startTimer = microtime(true);
        $path             = $config['app']['LOG_PATH'];
        $path             = DS == substr($path, -1) ? $path : $path . DS;
        $path             .= date('Y-m-d') . $config['app']['LOG_FILE_EXTENSION'];
        
        if (!file_exists(dirname($path)))
        {
            if (!mkdir(dirname($path), 0755, true))
            {
                throw new \Exception('The log directory cannot be written.', 500);
            }
        }
        
        $this->addWriter(new writer\File($path));
        $this->timestampFormat = $config['app']['LOG_DATE_FORMAT'];
    }
    
    /**
     * Add a log writer
     *
     * @param  writer\WriterInterface $writer
     *
     * @return LoggerProvider
     */
    public function addWriter(writer\WriterInterface $writer)
    {
        $this->writers[] = $writer;
        
        return $this;
    }
    
    /**
     * Add an INFO log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function info($message, array $context = [])
    {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Add a log entry
     *
     * @param  mixed $level
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->startTimer)
        {
            $this->startTimer = microtime(true);
        }
        
        static::$startNumber++;
        
        $number               = str_pad(static::$startNumber, 3, "0", STR_PAD_RIGHT);
        $time                 = number_format((microtime(true) - $this->startTimer), 4);
        $context['timestamp'] = "{$number}  " . (new \DateTime())->format($this->timestampFormat) . " {$time}";
        $context['name']      = $this->levels[$level];
        $message              = (string)$message;
        
        // 写入缓冲区
        if (empty($this->writers))
        {
            $this->buffer[] = [$level, $message, $context];
        }
        
        foreach ($this->writers as $writer)
        {
            if (!empty($this->buffer))
            {
                foreach ($this->buffer as $buffer)
                {
                    $writer->writeLog($buffer[0], $buffer[1], $buffer[2]);
                    $this->buffer = [];
                }
            }
            else
            {
                $writer->writeLog($level, $message, $context);
            }
            
        }
        
        return $this;
    }
    
    /**
     * Add log writers
     *
     * @param  array $writers
     *
     * @return LoggerProvider
     */
    public function addWriters(array $writers)
    {
        foreach ($writers as $writer)
        {
            $this->addWriter($writer);
        }
        
        return $this;
    }
    
    /**
     * Get all log writers
     *
     * @return array
     */
    public function getWriters()
    {
        return $this->writers;
    }
    
    /**
     * Get timestamp format
     *
     * @return string
     */
    public function getTimestampFormat()
    {
        return $this->timestampFormat;
    }
    
    /**
     * Set timestamp format
     *
     * @param  string $format
     *
     * @return LoggerProvider
     */
    public function setTimestampFormat($format = 'Y-m-d H:i:s')
    {
        $this->timestampFormat = $format;
        
        return $this;
    }
    
    /**
     * Add an EMERGENCY log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Add an ALERT log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function alert($message, array $context = [])
    {
        return $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Add a CRITICAL log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function critical($message, array $context = [])
    {
        return $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Add an ERROR log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function error($message, array $context = [])
    {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Add a WARNING log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function warning($message, array $context = [])
    {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Add a NOTICE log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function notice($message, array $context = [])
    {
        return $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Add a DEBUG log entry
     *
     * @param  mixed $message
     * @param  array $context
     *
     * @return LoggerProvider
     */
    public function debug($message, array $context = [])
    {
        return $this->log(self::DEBUG, $message, $context);
    }
}