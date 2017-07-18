<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\router;


use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\Provider;


class RouterProvider extends Provider
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * 请求别名
     *
     * @var array
     */
    private $pathInfoAlias = [];
    
    /**
     * 请求URL后缀
     *
     * @var string
     */
    private $pathInfoSuffix = null;
    
    /**
     * 默认控制器
     *
     * @var string
     */
    private $defaultController = '';
    
    /**
     * 默认动作
     *
     * @var string
     */
    private $defaultAction = '';
    
    /**
     * 解析前请求路径
     *
     * @var string
     */
    private $requestUri;
    
    
    /**
     * 解析后请求路径
     *
     * @var string
     */
    private $pathInfoUri;
    /**
     * 解析后请求命名空间
     *
     * @var string
     */
    private $requestNamespace;
    
    /**
     * 解析后请求类名
     *
     * @var string
     */
    private $requestClassName;
    
    /**
     * 解析后请求方法
     *
     * @var string
     */
    private $requestMethod;
    
    /**
     * 解析后请求参数
     *
     * @var string
     */
    private $requestPathParams;
    
    /**
     * 请求路径
     *
     * @var string
     */
    private $requestPathInfo;
    
    /**
     * 控制器路径
     *
     * @var string
     */
    private $requestController;
    
    
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
     * @param ConfigProvider        $config
     * @param LoggerProvider|Logger $logger
     */
    public function boot(ConfigProvider $config, LoggerProvider $logger)
    {
        $this->pathInfoAlias     = $config['router']['REQUEST_ALIAS'];
        $this->pathInfoSuffix    = $config['router']['URL_SUFFIX'];
        $this->defaultController = $config['router']['DEFAULT_CONTROLLER'];
        $this->defaultAction     = $config['router']['DEFAULT_ACTION'];
        
        $this->logger = $logger;
        $this->logger->info($this->className() . " is booted");
    }
    
    /**
     * 解析URL路径信息
     */
    public function parseUrl()
    {
        list($this->requestUri, $this->pathInfoUri) = $this->_parse_uri();
        
        return $this->pathInfoUri;
    }
    
    /**
     * 解析 URI
     *
     * @param null|string $url URL
     *
     * @return array
     */
    protected function _parse_uri(string $url = null)
    {
        $path = $url ?: ($_SERVER['PATH_INFO'] ?? @getenv('PATH_INFO'));
        
        if (trim($path, '/') and $path != ('/' . APP_ENTRY))
        {
            return $this->_get_uri_str($path);
        }
        
        $path = $_SERVER['QUERY_STRING'] ?? @getenv('QUERY_STRING');
        
        if (trim($path, '/'))
        {
            return $this->_get_uri_str($path);
        }
        
        // ? mode
        if (is_array($_GET) and count($_GET) > 0 and trim(key($_GET), '/'))
        {
            return $this->_get_uri_str($path);
        }
    }
    
    /**
     * 获取 URI 字符串
     *
     * @param string $str
     *
     * @return array
     */
    protected function _get_uri_str(string $str)
    {
        if (false !== strpos($str, '&'))
        {
            $str = substr($str, 0, strpos($str, '&'));
        }
        
        $str = trim($str, '/');
        
        if (!empty($this->pathInfoSuffix))
        {
            if (($pos = stripos($str, $this->pathInfoSuffix)))
            {
                $str = substr($str, 0, $pos) . substr(($str + strlen($this->pathInfoSuffix)), $pos);
            }
        }
        
        return [$str, $this->_parse_router_alias($str)];
    }
    
    /**
     * 解析路由别名
     *
     * @param string $str
     *
     * @return string
     */
    protected function _parse_router_alias(string $str)
    {
        foreach ($this->pathInfoAlias as $k => $v)
        {
            if (0 === stripos($str, $k))
            {
                return $v . substr($str, strlen($k));
            }
        }
        
        return $str;
    }
    
    /**
     * 获取解析后路径
     *
     * @return mixed
     */
    public function getPathInfoUri()
    {
        return $this->pathInfoUri;
    }
    
    /**
     * 获取原始请求路径
     *
     * @return mixed
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }
    
    /**
     * 解析控制器信息
     *
     * @param string $pathInfoUri 请求信息
     *
     * @return array
     */
    public function parseController($pathInfoUri = null)
    {
        // find controller
        $pathParams = $pathInfoUri ? explode('/', $pathInfoUri) : explode('/', $this->defaultController);
        
        list($file, $name) = $this->_find_controller($pathParams);
        
        // parse namespace
        $namespace = str_replace([dirname(APP_PATH), substr($file, strripos($file, DS))], '', $file);
        $namespace = str_replace('/', '\\', $namespace);
        
        // parse controller name
        $classNameArr = $name;
        $className    = UcWords(array_pop($name)) . 'Controller';
        
        $fullClassName = $namespace . '\\' . $className;
        
        // remove controller item
        $pathParams = array_splice($pathParams, count($classNameArr));
        
        if (!class_exists($fullClassName))
        {
            trigger_error("Controller class \"$fullClassName\" isn't exist.", E_USER_ERROR);
        }
        
        $this->logger->info("Parse router is \"$fullClassName\"");
        
        // check controller father class
//        if ('eiu\abstracts\controller\Controller' != get_parent_class($fullClassName))
//        {
//            trigger_error("Controller class \"$fullClassName\" inherited error.", E_USER_ERROR);
//        }
        
        // find action
        $method = null;
        
        if (current($pathParams))
        {
            $method = array_shift($pathParams);
        }
        else if (method_exists($fullClassName, '_' . $this->defaultAction))
        {
            $method = $this->defaultAction;
        }
        else
        {
            trigger_error("Controller \"$fullClassName\" missing action.", E_USER_ERROR);
        }
        
        $pathInfo   = $pathInfo = join('/', $classNameArr) . '/' . $method;
        $controller = trim(str_replace($method, '', $pathInfo), '/');
        
        $this->requestNamespace  = $namespace;
        $this->requestClassName  = $className;
        $this->requestMethod     = $method;
        $this->requestPathParams = $pathParams;
        $this->requestPathInfo   = $pathInfo;
        $this->requestController = $controller;
        
        return compact('namespace', 'className', 'controller', 'method', 'pathParams', 'pathInfo');
    }
    
    /**
     * 通过 URI 查找控制器
     *
     * @param array $uri URI
     *
     * @return array
     */
    private function _find_controller(array $uri)
    {
        $dir  = APP_PATH . 'modules' . DS . 'controllers' . DS;
        $path = null;
        $file = null;
        $name = null;
        
        // find path
        for ($c = count($uri), $i = $c; $i != 0; $i--)
        {
            // current path array
            $cur_uri = array_slice($uri, 0, $i);
            
            // get end item
            $end_uri = $cur_uri[$i - 1];
            
            // build path
            $path = $dir . implode(DS, $cur_uri) . DS;
            $file = $path . ucwords($end_uri) . "Controller.php";
            
            // find out
            if (is_file($file))
            {
                $name = array_slice($uri, 0, $i);
                unset($cur_uri, $path);
                break;
            }
            
            // next level directory
            $cur_uri = array_slice($uri, 0, $i - 1);
            
            // current directory
            $cur_dir = implode(DS, $cur_uri);
            
            // can not find
            if (!$cur_dir)
            {
                trigger_error("Controller load failure, maybe it's not exist.", E_USER_ERROR);
            }
            
            // build
            $path = $dir . $cur_dir . DS;
            $file = $path . ucwords($end_uri) . "Controller.php";
            
            // find out
            if (is_file($file))
            {
                $name = array_slice($uri, 0, $i);
                unset($cur_uri, $path);
                break;
            }
        }
        
        return [$file, $name];
    }
    
    /**
     * 获取请求命名空间
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->requestNamespace;
    }
    
    /**
     * 获取请求类名
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->requestClassName;
    }
    
    /**
     * 获取请求方法名
     *
     * @return string
     */
    public function getMethodName()
    {
        return $this->requestMethod;
    }
    
    /**
     * 获取请求URL参数组
     *
     * @return string
     */
    public function getPathParams()
    {
        return $this->requestPathParams;
    }
}