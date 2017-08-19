<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\view;

/**
 * 视图扩展类
 *
 * 用于扩展视图模板父类
 */
class ViewExt
{
    /**
     * 视图模板实例
     *
     * @var object|ViewExt
     */
    static private $_instance;
    
    /**
     * 构造方法
     */
    private function __construct()
    {
    }
    
    /**
     * 获取实例
     *
     * @return object|ViewExt
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self))
        {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    /**
     * 克隆方法
     */
    public function __clone()
    {
    }
    
    /**
     * 获取 SESSION
     *
     * @param string $key 索引
     *
     * @return mixed
     */
    public function session(string $key)
    {
        return parent::get_session($key);
    }
    
    /**
     * 生成 URL
     *
     * @param    string  $pathinfo 路径信息
     * @param array      $params   参数
     * @param bool|false $full     是否完整 URL
     *
     * @return string
     */
    public function url(string $pathinfo = null, array $params = [], bool $full = false)
    {
        return parent::get_url($pathinfo, $params, $full);
    }
    
    /**
     * 创建 CSRF
     *
     * @param string $key 密钥
     *
     * @return string
     */
    public function csrf(string $key)
    {
        return $this->call('core', 'security')->create_csrf($key);
    }
    
    /**
     * 继承模板
     *
     * @param string $path 模板路径
     */
    public function extend(string $path)
    {
        return $this->call('core', 'view')->check_template($path);
    }
    
    /**
     * 获取当前路由信息
     *
     * @param string $index 索引
     *
     * @return mixed
     */
    public function router($index = null)
    {
        return $this->call('core', 'router')->get_info($index);
    }
    
    /**
     * 获取系统地图对象
     *
     * @param string $service 服务路径
     *
     * @return object
     */
    public function service(string $service)
    {
        return $this->call('service', $service);
    }
    
}