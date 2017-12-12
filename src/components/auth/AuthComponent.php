<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;


use eiu\components\auth\adapter\Jwt;
use eiu\components\auth\adapter\Session;
use eiu\components\Component;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\router\RequestProvider;
use eiu\core\service\view\ViewProvider;
use Exception;


/**
 * 认证组件
 *
 * @package eiu\components\auth
 */
class AuthComponent extends Component
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * @var ViewProvider
     */
    private $view;
    
    /**
     * @var string
     */
    private $url;
    
    /**
     * @var string
     */
    private $controller;
    
    /**
     * @var string
     */
    private $action;
    
    /**
     * @var array
     */
    private $config;
    
    /**
     * @var IAuthAdapter
     */
    private $adapter;
    
    /**
     * SessionComponent constructor.
     *
     * @param Application     $app
     * @param RequestProvider $request
     * @param ViewProvider    $view
     * @param ConfigProvider  $config
     * @param LoggerProvider  $logger
     *
     * @throws Exception
     *
     */
    public function __construct(Application $app, RequestProvider $request, ViewProvider $view, ConfigProvider $config, LoggerProvider $logger)
    {
        parent::__construct($app);
        
        $this->url        = $request->router('pathInfo');
        $this->controller = $request->router('controller');
        $this->action     = $request->router('method');
        $this->view       = $view;
        $this->logger     = $logger;
        $this->config     = $config['auth'];
        
        if (!isset($config['auth']['KEY']) or !$config['auth']['KEY'])
        {
            throw new Exception("Undefined auth key");
        }
        
        switch ($config['auth']['MODE'])
        {
            case 'jwt':
                $this->adapter = $this->app->make(Jwt::class);
                break;
            
            case 'session':
                $this->adapter = $this->app->make(Session::class);
                break;
            
            default:
                throw new Exception("Undefined auth adapter \"{$config['auth']['MODE']}\"");
        }
        
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 设置认证状态为已登录
     *
     * @param array $data
     *
     * @return string
     */
    public function setLogined(array $data = [])
    {
        return $this->adapter->create($data, $this->config['LIFETIME']);
    }
    
    /**
     * 判断当前用户是否登录
     *
     * @return bool
     */
    public function isLogined()
    {
        return $this->adapter->verify();
    }
    
    /**
     * 刷新当前登录状态
     *
     * @return mixed
     */
    public function refresh()
    {
        return $this->adapter->refresh();
    }
    
    /**
     * 退出登录
     */
    public function logout()
    {
        $this->adapter->clear();
    }
    
    /**
     * 检查当前访问是否已登录
     *
     * @return bool
     */
    public function checkLogin()
    {
        // 全局免登陆
        if ($this->config['LOGIN_EXEMPT'] === '*')
        {
            return true;
        }
        
        // 基于配置的登录豁免
        if (true === $this->checkByConfig($this->config['LOGIN_EXEMPT']))
        {
            return true;
        }
        
        if ($this->adapter->verify())
        {
            // 获取登录数据
            $this->loginData = $this->adapter->data();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 基于配置的访问验证
     *
     * @param array $config
     *
     * @return bool
     */
    private function checkByConfig(array $config)
    {
        if (!is_array($config) or empty($config))
        {
            $this->logger->warning("The auth configuration list is empty.");
            
            return false;
        }
        
        $temp_path   = $this->url;
        $temp_config = [];
        
        foreach ($config as $v)
        {
            $temp_config[] = strtolower($v);
        }
        
        while (true)
        {
            if (!in_array($temp_path, $temp_config))
            {
                if (false === ($index = strripos($temp_path, '/')))
                {
                    return false;
                }
                
                $temp_path = substr($temp_path, 0, $index);
                continue;
            }
            
            return true;
        }
        
        return false;
    }
}