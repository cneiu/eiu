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
    
}