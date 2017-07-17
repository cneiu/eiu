<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\session;


use ArrayAccess;
use eiu\components\Component;
use eiu\components\cryptography\encryption\EncrypterComponent;
use eiu\components\cryptography\encryption\keys\Key;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use Exception;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class SessionComponent extends Component implements ArrayAccess
{
    /**
     * SessionComponent ID
     *
     * @var string
     */
    private $sessionId = null;
    
    /**
     * @var EncrypterComponent
     */
    private $encrypter;
    
    /**
     * @var ConfigProvider
     */
    private $config;
    
    /**
     * SessionComponent constructor.
     *
     * @param Application           $app
     * @param ConfigProvider        $config
     * @param LoggerProvider|Logger $logger
     * @param EncrypterComponent    $encrypter
     */
    public function __construct(Application $app, ConfigProvider $config, LoggerProvider $logger, EncrypterComponent $encrypter)
    {
        parent::__construct($app);
        
        if (empty(session_id()))
        {
            session_save_path(APP_DATA . 'session');
            session_start();
            
            $this->sessionId = session_id();
            $this->init();
        }
        
        $this->config    = $config;
        $this->encrypter = $encrypter;
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 初始化会话
     *
     * @return void
     */
    private function init()
    {
        if (!isset($_SESSION['_EIU']))
        {
            $_SESSION['_EIU'] = [
                'requests'    => [],
                'expirations' => [],
            ];
        }
        else if (isset($_SESSION['_EIU']) && !isset($_SESSION['_EIU']['requests']))
        {
            $_SESSION['_EIU']['requests']    = [];
            $_SESSION['_EIU']['expirations'] = [];
        }
        else
        {
            $this->checkRequests();
            $this->checkExpirations();
        }
    }
    
    /**
     * 检查请求次数
     *
     * @return void
     */
    private function checkRequests()
    {
        foreach ($_SESSION as $key => $value)
        {
            if (isset($_SESSION['_EIU']['requests'][$key]))
            {
                $_SESSION['_EIU']['requests'][$key]['current']++;
                if ($_SESSION['_EIU']['requests'][$key]['current'] > $_SESSION['_EIU']['requests'][$key]['limit'])
                {
                    unset($_SESSION[$key]);
                    unset($_SESSION['_EIU']['requests'][$key]);
                }
            }
        }
    }
    
    /**
     * 检查会话超时
     *
     * @return void
     */
    private function checkExpirations()
    {
        foreach ($_SESSION as $key => $value)
        {
            if (isset($_SESSION['_EIU']['expirations'][$key]) && (time() > $_SESSION['_EIU']['expirations'][$key]))
            {
                unset($_SESSION[$key]);
                unset($_SESSION['_EIU']['expirations'][$key]);
            }
        }
    }
    
    /**
     * 设置一个有过期时间的会话项
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  int    $expire
     *
     * @return SessionComponent
     */
    public function setTimedValue($key, $value, $expire = 300)
    {
        $_SESSION[$key]                        = $value;
        $_SESSION['_EIU']['expirations'][$key] = time() + (int)$expire;
        
        return $this;
    }
    
    /**
     * 设置一个有请求次数限制的会话项
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  int    $hops
     *
     * @return SessionComponent
     */
    public function setRequestValue($key, $value, $hops = 1)
    {
        $_SESSION[$key]                     = $value;
        $_SESSION['_EIU']['requests'][$key] = [
            'current' => 0,
            'limit'   => (int)$hops,
        ];
        
        return $this;
    }
    
    /**
     * 获取会话ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->sessionId;
    }
    
    /**
     * 重建会话ID
     *
     * @return void
     */
    public function regenerateId()
    {
        session_regenerate_id();
        $this->sessionId = session_id();
    }
    
    /**
     * 销毁所有会话
     *
     * @return void
     */
    public function kill()
    {
        $_SESSION = null;
        session_unset();
        session_destroy();
        unset($this->sessionId);
    }
    
    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @throws Exception
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }
    
    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
    
    /**
     * Get method to return the value of the $_SESSION global variable
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $value = null;
        
        if (isset($_SESSION[$name]))
        {
            $value = $this->encrypter->init(new Key($this->config['app']['KEY']))->decrypt($_SESSION[$name]);
        }
        
        return $value;
    }
    
    /**
     * Set a property in the session object that is linked to the $_SESSION global variable
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $value           = $this->encrypter->init(new Key($this->config['app']['KEY']))->encrypt($value);
        $_SESSION[$name] = $value;
    }
    
    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }
    
    /**
     * Return the isset value of the $_SESSION global variable
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }
    
    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     *
     * @throws Exception
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }
    
    /**
     * Unset the $_SESSION global variable
     *
     * @param  string $name
     *
     * @return void
     */
    public function __unset($name)
    {
        $_SESSION[$name] = null;
        unset($_SESSION[$name]);
    }
}