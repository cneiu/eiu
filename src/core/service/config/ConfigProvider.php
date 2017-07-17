<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\config;


use ArrayAccess;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;


class ConfigProvider extends Provider implements ArrayAccess
{
    /**
     * 配置文件目录
     *
     * @var null
     */
    static protected $path = null;
    
    /**
     * 配置存储
     *
     * @var array
     */
    static protected $configs = [];
    
    /**
     * 服务注册
     */
    public function register()
    {
        // 填充到容器
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
        static::$path = APP_PATH . 'configs' . DS;
        
        if (!is_file($file = static::$path . 'app.config.php'))
        {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            trigger_error('ApplicationContract configuration file does not exist.', E_USER_ERROR);
        }
        
        if (!is_array($_config = include($file)))
        {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            trigger_error('App configuration item invalid.', E_USER_ERROR);
        }
        
        // 设置字符集
        ini_set('default_charset', strtoupper($_config['CHARSET']));
        
        if (function_exists("mb_language") and function_exists("mb_regex_encoding") and function_exists("mb_internal_encoding"))
        {
            mb_language('uni');
            mb_regex_encoding($_config['CHARSET']);
            mb_internal_encoding($_config['CHARSET']);
        }
        
        // 设置时区
        date_default_timezone_set($_config['TIMEZONE']);
        
        // 设置本位币
        setlocale(LC_MONETARY, $_config['MONETARY']);
        
        $logger->info($this->className() . " is booted");
    }
    
    /**
     * 设置配置项
     *
     * @param string $namespace 配置组
     * @param string $key       键
     * @param mixed  $value     值
     */
    public function set(string $namespace, string $key, $value)
    {
        self::$configs[$namespace][$key] = $value;
    }
    
    /**
     * Whether a offset exists
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset(self::$configs[$offset]);
    }
    
    /**
     * Offset to retrieve
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    /**
     * 获取配置项
     *
     * 从全局配置中获取配置项
     *
     * @param string      $namespace 配置组
     * @param string|null $key       配置索引
     *
     * @return mixed
     */
    public function get(string $namespace, string $key = null)
    {
        if (!isset(self::$configs[$namespace]))
        {
            $file = static::$path . strtolower($namespace) . '.config.php';
            
            if (!is_file($file))
            {
                header('HTTP/1.1 503 Service Unavailable.', true, 503);
                trigger_error("The $namespace configuration file does not exist.", E_USER_ERROR);
            }
            
            self::$configs[$namespace] = include($file);
        }
        
        if (!$key)
        {
            return self::$configs[$namespace];
        }
        
        if (!isset(self::$configs[$namespace][$key]))
        {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            trigger_error("The $namespace index $key does not exist.", E_USER_ERROR);
        }
        
        return self::$configs[$namespace][$key];
    }
    
    /**
     * Offset to set
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        self::$configs[$offset] = $value;
    }
    
    /**
     * Offset to unset
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset(self::$configs[$offset]);
    }
}