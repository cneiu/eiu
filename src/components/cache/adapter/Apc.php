<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache\adapter;


use eiu\components\cache\CacheException;


/**
 * APC 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Apc extends AbstractAdapter
{
    
    /**
     * Constructor
     *
     * Instantiate the APC cache object
     *
     * @param  int $ttl
     *
     * @throws Exception
     */
    public function __construct($ttl = 0)
    {
        parent::__construct($ttl);
        
        if (!function_exists('apc_cache_info'))
        {
            throw new CacheException('APC is not available');
        }
    }
    
    /**
     * 获取缓存信息
     *
     * @return array
     */
    public function getInfo()
    {
        return apc_cache_info();
    }
    
    /**
     * 获取指定缓存过期时间
     *
     * @param  string $id
     *
     * @return int
     */
    public function getItemTtl($id)
    {
        $cacheValue = apc_fetch($id);
        $ttl        = 0;
        
        if ($cacheValue !== false)
        {
            $ttl = $cacheValue['ttl'];
        }
        
        return $ttl;
    }
    
    /**
     * 写入一个缓存
     *
     * @param  string $id
     * @param  mixed  $value
     * @param  int    $ttl
     *
     * @return Apc
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $cacheValue = [
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ];
        
        apc_store($id, $cacheValue, $cacheValue['ttl']);
        
        return $this;
    }
    
    /**
     * 获取指定缓存
     *
     * @param  string $id
     *
     * @return mixed
     */
    public function getItem($id)
    {
        $cacheValue = apc_fetch($id);
        $value      = false;
        
        if ($cacheValue !== false)
        {
            $value = $cacheValue['value'];
        }
        
        return $value;
    }
    
    /**
     * 判断指定缓存是否存在
     *
     * @param  string $id
     *
     * @return boolean
     */
    public function hasItem($id)
    {
        $cacheValue = apc_fetch($id);
        
        return ($cacheValue !== false);
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Apc
     */
    public function deleteItem($id)
    {
        apc_delete($id);
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return Apc
     */
    public function destroy()
    {
        $this->clear();
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Apc
     */
    public function clear()
    {
        apc_clear_cache();
        apc_clear_cache('user');
        
        return $this;
    }
}
