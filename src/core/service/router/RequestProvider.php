<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\router\request;


use ArrayAccess;
use eiu\components\cookie\CookieComponent;
use eiu\components\session\SessionComponent;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;
use eiu\core\service\security\SecurityProvider;


class RequestProvider extends Provider implements ArrayAccess
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * 请求数组
     *
     * @var array
     */
    private $requestData = [];
    
    /**
     * @var SecurityProvider
     */
    private $security;
    
    /**
     * @var ConfigProvider
     */
    private $config;
    
    /**
     * @var SessionComponent
     */
    private $session;
    
    /**
     * @var CookieComponent
     */
    private $cookie;
    
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
     * @param ConfigProvider        $config
     * @param LoggerProvider|Logger $logger
     * @param SecurityProvider      $security
     * @param SessionComponent      $session
     * @param CookieComponent       $cookie
     */
    public function boot(ConfigProvider $config, LoggerProvider $logger, SecurityProvider $security, SessionComponent $session, CookieComponent $cookie)
    {
        $this->config   = $config;
        $this->logger   = $logger;
        $this->security = $security;
        $this->session  = $session;
        $this->cookie   = $cookie;
        
        $this->requestData             = [];
        $this->requestData['router']   = [];
        $this->requestData['globals']  = [];
        $this->requestData['env']      = [];
        $this->requestData['post']     = [];
        $this->requestData['get']      = [];
        $this->requestData['cookie']   = [];
        $this->requestData['server']   = [];
        $this->requestData['files']    = [];
        $this->requestData['request']  = [];
        $this->requestData['constant'] = [];
        $this->requestData['input']    = [];
        $this->requestData['header']   = [];
        $this->requestData['session']  = [];
        
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * 包装生成
     *
     * @param $router
     *
     * @return $this
     */
    public function setRouter($router)
    {
        $this->requestData['router'] = $router;
        
        return $this;
    }
    
    /**
     * 生成 URL
     *
     * @param string|null $pathInfo 路径信息
     * @param array       $params   参数
     * @param bool        $full     是否生成完整路径
     *
     * @return string
     */
    public function getUrl(string $pathInfo = null, array $params = [], bool $full = false)
    {
        $style    = $this->config['router']['URL_STYLE'];
        $suffix   = $this->config['router']['URL_SUFFIX'];
        $alias    = array_flip($this->config['router']['REQUEST_ALIAS']);
        $pathInfo = urldecode($pathInfo);
        $protocol = $this->isHttps() ? 'https://' : 'http://';
        $port     = $this->server('SERVER_PORT');
        $port     = ($port == 80 or !$port) ? '' : ":{$port}";
        $host     = $this->server('SERVER_NAME');
        $host     = $full ? $protocol . $host . $port . APP_URL : APP_URL;
        
        if (!$pathInfo)
        {
            $pathInfo = $this->router('pathInfo');
        }
        
        if ('/' == $pathInfo)
        {
            return $host;
        }
        
        foreach ($alias as $k => $v)
        {
            if (0 === stripos($pathInfo, $k))
            {
                $pathInfo = $v . substr($pathInfo, strlen($k));
            }
        }
        
        $get_params  = '';
        $path_params = '';
        
        if (is_array($params))
        {
            $path_params = [];
            $get_params  = [];
            
            foreach ($params as $k => $v)
            {
                if (is_numeric($k))
                {
                    $path_params[] = $v;
                }
                else if (is_string($k))
                {
                    $get_params[] = "$k=$v";
                }
            }
            
            $path_params = implode('/', $path_params);
            $path_params = (!$path_params ? '' : '/') . $path_params;
            $get_params  = implode('&', $get_params);
        }
        
        switch ($style)
        {
            case 'rewrite':
                $suffix     = $pathInfo ? '' : $suffix;
                $get_params = (!$get_params ? '' : '?') . $get_params;
                $url        = "{$host}{$pathInfo}{$path_params}{$suffix}{$get_params}";
                break;
            
            default:
                $get_params = (!$get_params ? '' : '&') . $get_params;
                $url        = "{$host}?{$pathInfo}{$path_params}{$get_params}";
        }
        
        return $url;
    }
    
    /**
     * 是否 HTTPS 模式
     *
     * @return bool
     */
    public function isHttps()
    {
        if (!empty($_SERVER['HTTPS']) and strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return true;
        }
        else if (isset($_SERVER['HTTP_X_ForWARDED_PROTO']) and strtolower($_SERVER['HTTP_X_ForWARDED_PROTO']) === 'https')
        {
            return true;
        }
        else if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) and strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * server
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array
     */
    public function header($index = null, $xss_clean = true)
    {
        $headers = [];
        
        // In Apache, you can simply call apache_request_headers()
        if (function_exists('apache_request_headers'))
        {
            $headers = apache_request_headers();
        }
        else
        {
            isset($_SERVER['CONTENT_TYPE']) and $_SERVER['CONTENT_TYPE'];
            
            foreach ($_SERVER as $key => $val)
            {
                if (sscanf($key, 'HTTP_%s', $header) === 1)
                {
                    // take SOME_HEADER and turn it into Some-Header
                    $header = str_replace('_', ' ', strtolower($header));
                    $header = str_replace(' ', '-', ucwords($header));
                    
                    $headers[$header] = $_SERVER[$key];
                }
            }
        }
        
        $this->requestData['header'] = $this->_fetch_from_array($headers, null, $xss_clean);
        
        return $index ? $this->requestData['header'][$index] ?? null : $this->requestData['header'];
    }
    
    /**
     * header
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function server($index = null, $xss_clean = true)
    {
        $this->requestData['server'] = $this->_fetch_from_array($_SERVER, null, $xss_clean);
        
        return $index ? $this->requestData['server'][$index] ?? null : $this->requestData['server'];
    }
    
    /**
     * router
     *
     * @param string|null $index 索引
     *
     * @return array|mixed
     *
     */
    public function router($index = null)
    {
        return $index ? $this->requestData['router'][$index] ?? null : $this->requestData['router'];
    }
    
    /**
     * globals
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function globals($index = null, $xss_clean = true)
    {
        $this->requestData['globals'] = $this->_fetch_from_array($GLOBALS, null, $xss_clean);
        
        return $index ? $this->requestData['globals'][$index] ?? null : $this->requestData['globals'];
    }
    
    /**
     * env
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function env($index = null, $xss_clean = true)
    {
        $this->requestData['env'] = $this->_fetch_from_array($_ENV, null, $xss_clean);
        
        return $index ? $this->requestData['env'][$index] ?? null : $this->requestData['env'];
    }
    
    /**
     * post
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function post($index = null, $xss_clean = true)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST))
        {
            if (0 === stripos($this->server('CONTENT_TYPE'), 'application/json'))
            {
                $_POST = json_decode(file_get_contents('php://input'), true);
            }
            else
            {
                if (!is_array($_POST))
                {
                    parse_str($_POST, $_POST);
                }
            }
        }
        
        $_POST = is_array($_POST) ? $_POST : [];
        
        if (!$xss_clean)
        {
            return $index ? $_POST[$index] : $_POST;
        }
        
        $this->requestData['post'] = $this->_fetch_from_array($_POST, null, $xss_clean);
        
        return $index ? $this->requestData['post'][$index] ?? null : $this->requestData['post'];
    }
    
    /**
     * get
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function get($index = null, $xss_clean = true)
    {
        $this->requestData['get'] = $this->_fetch_from_array($_GET, null, $xss_clean);
        
        return $index ? $this->requestData['get'][$index] ?? null : $this->requestData['get'];
    }
    
    /** cookie
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function cookie($index = null, $xss_clean = true)
    {
        $this->requestData['cookie'] = $this->_fetch_from_array($this->cookie, null, $xss_clean);
        
        return $index ? $this->requestData['cookie'][$index] ?? null : $this->requestData['cookie'];
    }
    
    /** session
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function session($index = null, $xss_clean = true)
    {
        $this->requestData['session'] = $this->_fetch_from_array($this->session, null, $xss_clean);
        
        return $index ? $this->requestData['session'][$index] ?? null : $this->requestData['session'];
    }
    
    /**
     * files
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array
     */
    public function files($index = null, $xss_clean = true)
    {
        $this->requestData['files'] = $this->_fetch_from_array($_FILES, null, $xss_clean);
        
        return $index ? $this->requestData['files'][$index] ?? null : $this->requestData['files'];
    }
    
    /**
     * request
     *
     * @param string|null $index     索引
     * @param bool        $xss_clean 是否 XSS 过滤
     *
     * @return array|mixed
     */
    public function request($index = null, $xss_clean = true)
    {
        $this->requestData['request'] = $this->_fetch_from_array($_REQUEST, null, $xss_clean);
        
        return $index ? $this->requestData['request'][$index] ?? null : $this->requestData['request'];
    }
    
    /**
     * constant
     *
     * @return array
     */
    public function constant()
    {
        $constants = get_defined_constants(true);
        $constants = $constants['user'];
        
        ksort($constants);
        
        return $this->requestData['constant'] = $constants;
    }
    
    /**
     * input
     *
     * 获取 php://input 的值
     *
     * @param    string $index     索引
     * @param    bool   $xss_clean 是否 XSS 过滤
     *
     * @return mixed
     */
    public function input(string $index = null, bool $xss_clean = true)
    {
        $data = file_get_contents('php://input');
        
        if (!is_array($data))
        {
            parse_str($data, $data);
        }
        
        if ($index)
        {
            return $this->_fetch_from_array($data, $index, $xss_clean);
        }
        
        $this->requestData['input'] = $this->_fetch_from_array($data, null, $xss_clean);
        
        return $index ? $this->requestData['input'][$index] : $this->requestData['input'];
    }
    
    /**
     * 是否 POST 请求
     *
     * @return bool
     */
    public function isPost()
    {
        return strtoupper($this->getRequestMethod()) == 'POST';
    }
    
    /**
     * 是否 GET 请求
     *
     * @return bool
     */
    public function isGet()
    {
        return strtoupper($this->getRequestMethod()) == 'GET';
    }
    
    /**
     * 是否 PUT 请求
     *
     * @return bool
     */
    public function isPut()
    {
        return strtoupper($this->getRequestMethod()) == 'PUT';
    }
    
    /**
     * 是否 HEAD 请求
     *
     * @return bool
     */
    public function isHead()
    {
        return strtoupper($this->getRequestMethod()) == 'HEAD';
    }
    
    /**
     * 是否 OPTIONS 请求
     *
     * @return bool
     */
    public function isOptions()
    {
        return strtoupper($this->getRequestMethod()) == 'OPTIONS';
    }
    
    /**
     * 是否 SOAP 请求
     *
     * @return    bool
     */
    public function isSoap()
    {
        return (false === stristr($_SERVER['HTTP_USER_AGENT'], 'PHP-SOAP')) ? false : true;
    }
    
    /**
     * 是否 ajax 请求
     *
     * @return    bool
     */
    public function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
    
    /**
     * 是否命令行
     *
     * @return bool
     */
    public function isCli()
    {
        return (PHP_SAPI === 'cli' or defined('STDIN'));
    }
    
    /**
     * 获取请求方法
     *
     * @param    bool $upper 是否大写
     *
     * @return    string
     */
    public function getRequestMethod(bool $upper = true)
    {
        return $upper ? strtoupper($_SERVER['REQUEST_METHOD']) : strtolower($_SERVER['REQUEST_METHOD']);
    }
    
    /**
     * 数组安全过滤
     *
     * @param    array &$array    $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param          $index     Index for item to be fetched from $array
     * @param    bool  $xss_clean Whether to apply XSS filtering
     *
     * @return    mixed
     */
    private function _fetch_from_array(array $array, $index = null, $xss_clean = false)
    {
        // If $index is NULL, it means that the whole $array is requested
        isset($index) or $index = array_keys($array);
        
        // allow fetching multiple keys at once
        if (is_array($index))
        {
            $output = [];
            
            foreach ($index as $key)
            {
                $output[$key] = $this->_fetch_from_array($array, $key, $xss_clean);
            }
            
            return $output;
        }
        
        if (isset($array[$index]))
        {
            $value = $array[$index];
        }
        else if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        {
            $value = $array;
            
            for ($i = 0; $i < $count; $i++)
            {
                $key = trim($matches[0][$i], '[]');
                
                if ($key === '') // Empty notation will return the value as array
                {
                    break;
                }
                
                if (isset($value[$key]))
                {
                    $value = $value[$key];
                }
                else
                {
                    return null;
                }
            }
        }
        else
        {
            return null;
        }
        
        return ($xss_clean === true) ? $this->security->xss_clean($value) : $value;
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
        if (method_exists($this, $offset) and isset($this->requestData[$offset]))
        {
            $this->__get($offset);
        }
        
        return isset($this->requestData[$offset]);
    }
    
    /**
     * get
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->requestData[$name]) || empty($this->requestData[$name]))
        {
            if (method_exists($this, $name))
            {
                return $this->requestData[$name] = $this->$name();
            }
            
            return [];
        }
        
        return $this->requestData[$name];
    }
    
    /**
     * set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        // pass
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
        return $this->__get($offset);
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
        // pass
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
        // pass
    }
}