<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cookie;


use ArrayAccess;
use eiu\components\Component;
use eiu\components\cryptography\encryption\EncrypterComponent;
use eiu\components\cryptography\encryption\keys\Key;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\router\RequestProvider;
use Exception;


/**
 * Class EventProvider
 *
 * @package eiu\core\service\event
 */
class CookieComponent extends Component implements ArrayAccess
{
    /**
     * CookieComponent IP
     *
     * @var string
     */
    private $ip = null;
    
    /**
     * CookieComponent Expiration
     *
     * @var int
     */
    private $expire = 0;
    
    /**
     * CookieComponent Path
     *
     * @var string
     */
    private $path = '/';
    
    /**
     * CookieComponent Domain
     *
     * @var string
     */
    private $domain = null;
    
    /**
     * CookieComponent Secure Flag
     *
     * @var boolean
     */
    private $secure = false;
    
    /**
     * CookieComponent HTTP Only Flag
     *
     * @var boolean
     */
    private $httponly = false;
    
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
    public function __construct(Application $app, ConfigProvider $config, LoggerProvider $logger, EncrypterComponent $encrypter, RequestProvider $request)
    {
        parent::__construct($app);
        
        if ($request->isCli())
        {
            return;
        }
        
        if ($config['app']['COOKIE_EXPIRE'])
        {
            $expire = $config['app']['COOKIE_EXPIRE'];
        }
        if ($config['app']['COOKIE_PATH'])
        {
            $path = $config['app']['COOKIE_PATH'];
        }
        if ($config['app']['COOKIE_DOMAIN'])
        {
            $domain = $config['app']['COOKIE_DOMAIN'];
        }
        if ($config['app']['COOKIE_SECURE'])
        {
            $secure = $config['app']['COOKIE_SECURE'];
        }
        if ($config['app']['COOKIE_HTTP_ONLY'])
        {
            $httponly = $config['app']['COOKIE_HTTP_ONLY'];
        }
        
        $this->setOptions(compact('expire', 'path', 'domain', 'secure', 'httponly'));
        
        $this->encrypter = $encrypter;
        $this->config    = $config;
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * Private method to set options
     *
     * @param  array $options
     *
     * @return CookieComponent
     */
    public function setOptions(array $options = [])
    {
        // Set the cookie owner's IP address and domain.
        $this->ip     = $_SERVER['REMOTE_ADDR'];
        $this->domain = $_SERVER['SERVER_NAME'];
        
        if (isset($options['expire']))
        {
            $this->expire = (int)$options['expire'];
        }
        if (isset($options['path']))
        {
            $this->path = $options['path'];
        }
        if (isset($options['domain']))
        {
            $this->domain = $options['domain'];
        }
        if (isset($options['secure']))
        {
            $this->secure = (bool)$options['secure'];
        }
        if (isset($options['httponly']))
        {
            $this->httponly = (bool)$options['httponly'];
        }
        
        return $this;
    }
    
    /**
     * Return the current cookie expiration
     *
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }
    
    /**
     * Return the current cookie path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Return the current cookie domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }
    
    /**
     * Return if the cookie is secure
     *
     * @return boolean
     */
    public function isSecure()
    {
        return $this->secure;
    }
    
    /**
     * Return if the cookie is HTTP only
     *
     * @return boolean
     */
    public function isHttpOnly()
    {
        return $this->httponly;
    }
    
    /**
     * Return the current IP address.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
    
    /**
     * Delete a cookie
     *
     * @param  string $name
     * @param  array  $options
     *
     * @return void
     */
    public function delete($name, array $options = null)
    {
        if (null !== $options)
        {
            $this->setOptions($options);
        }
        if (isset($_COOKIE[$name]))
        {
            setcookie($name, $_COOKIE[$name], (time() - 3600), $this->path, $this->domain, $this->secure, $this->httponly);
        }
    }
    
    /**
     * Clear (delete) all cookies
     *
     * @param  array $options
     *
     * @return void
     */
    public function clear(array $options = null)
    {
        if (null !== $options)
        {
            $this->setOptions($options);
        }
        foreach ($_COOKIE as $name => $value)
        {
            if (isset($_COOKIE[$name]))
            {
                setcookie($name, $_COOKIE[$name], (time() - 3600), $this->path, $this->domain, $this->secure, $this->httponly);
            }
        }
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
     * Get method to return the value of the $_COOKIE global variable
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $value = null;
        
        if (isset($_COOKIE[$name]))
        {
            $value = $this->encrypter->init(new Key($this->config['app']['KEY']))->decrypt($_COOKIE[$name]);
            $value = (substr($value, 0, 1) == '{') ? json_decode($value) : $value;
        }
        
        
        return $value;
    }
    
    /**
     * Set method to set the value of the $_COOKIE global variable
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $options = [
            'expire'   => $this->expire,
            'path'     => $this->path,
            'domain'   => $this->domain,
            'secure'   => $this->secure,
            'httponly' => $this->httponly,
        ];
        
        $value = $this->encrypter->init(new Key($this->config['app']['KEY']))->encrypt($value);
        
        if (null !== $options)
        {
            $this->setOptions($options);
        }
        
        if (!is_string($value) && !is_numeric($value))
        {
            $value = json_encode($value);
        }
        
        setcookie($name, $value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
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
     * Return the isset value of the $_COOKIE global variable
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($_COOKIE[$name]);
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
     * Unset the value in the $_COOKIE global variable
     *
     * @param  string $name
     *
     * @return void
     */
    public function __unset($name)
    {
        if (isset($_COOKIE[$name]))
        {
            setcookie($name, $_COOKIE[$name], (time() - 3600), $this->path, $this->domain, $this->secure, $this->httponly);
        }
    }
}