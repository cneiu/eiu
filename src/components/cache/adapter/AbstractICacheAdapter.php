<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache\adapter;

/**
 * 抽象缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
abstract class AbstractICacheAdapter implements ICacheAdapter
{
    
    /**
     * Global time-to-live
     *
     * @var int
     */
    protected $ttl = 0;
    
    /**
     * Constructor
     *
     * Instantiate the cache adapter object
     *
     * @param  int $ttl
     */
    public function __construct($ttl = 0)
    {
        $this->setTtl($ttl);
    }
    
    /**
     * 获取全局过期时间
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
    
    /**
     * 设置全局过期时间
     *
     * @param  int $ttl
     *
     * @return AbstractICacheAdapter
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int)$ttl;
        
        return $this;
    }
    
    /**
     * 获取指定缓存过期时间
     *
     * @param  string $id
     *
     * @return int
     */
    abstract public function getItemTtl($id);
    
    /**
     * 写入一个缓存
     *
     * @param  string $id
     * @param  mixed  $value
     * @param  int    $ttl
     *
     * @return void
     */
    abstract public function saveItem($id, $value, $ttl = null);
    
    /**
     * 获取指定缓存
     *
     * @param  string $id
     *
     * @return mixed
     */
    abstract public function getItem($id);
    
    /**
     * 判断指定缓存是否存在
     *
     * @param  string $id
     *
     * @return boolean
     */
    abstract public function hasItem($id);
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return void
     */
    abstract public function deleteItem($id);
    
    /**
     * 清除所有缓存
     *
     * @return void
     */
    abstract public function clear();
    
    /**
     * 销毁缓存器
     *
     * @return void
     */
    abstract public function destroy();
    
}
