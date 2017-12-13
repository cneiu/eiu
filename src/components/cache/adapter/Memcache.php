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
 * Memcache 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Memcache extends AbstractICacheAdapter
{
    
    /**
     * Memcache object
     *
     * @var \Memcache
     */
    protected $memcache = null;
    
    /**
     * Constructor
     *
     * Instantiate the memcache cache object
     *
     * @param  int    $ttl
     * @param  string $host
     * @param  int    $port
     *
     * @throws Exception
     */
    public function __construct($ttl = 0, $host = 'localhost', $port = 11211)
    {
        parent::__construct($ttl);
        
        if (!class_exists('Memcache', false))
        {
            throw new CacheException('Memcache is not available');
        }
        
        $this->memcache = new \Memcache();
        
        if (!$this->memcache->connect($host, (int)$port))
        {
            throw new CacheException('Unable to connect to the memcache server');
        }
    }
    
    /**
     * 获取缓存器
     *
     * @return \Memcache
     */
    public function memcache()
    {
        return $this->memcache;
    }
    
    /**
     * 获取缓冲器版本
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->memcache->getVersion();
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
        $cacheValue = $this->memcache->get($id);
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
     * @return Memcache
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $cacheValue = [
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ];
        
        $this->memcache->set($id, $cacheValue, false, $cacheValue['ttl']);
        
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
        $cacheValue = $this->memcache->get($id);
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
        $cacheValue = $this->memcache->get($id);
        
        return ($cacheValue !== false);
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Memcache
     */
    public function deleteItem($id)
    {
        $this->memcache->delete($id);
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Memcache
     */
    public function clear()
    {
        $this->memcache->flush();
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return Memcache
     */
    public function destroy()
    {
        $this->memcache->flush();
        $this->memcache = null;
        
        return $this;
    }
    
}
