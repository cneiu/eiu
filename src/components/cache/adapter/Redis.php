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
 * Redis 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Redis extends AbstractAdapter
{
    
    /**
     * Redis object
     *
     * @var \Redis
     */
    protected $redis = null;
    
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
    public function __construct($ttl = 0, $host = 'localhost', $port = 6379)
    {
        parent::__construct($ttl);
        
        if (!class_exists('Redis', false))
        {
            throw new CacheException('Redis is not available');
        }
        
        $this->redis = new \Redis();
        
        if (!$this->redis->connect($host, (int)$port))
        {
            throw new CacheException('Unable to connect to the redis server');
        }
    }
    
    /**
     * 获取缓存器
     *
     * @return \Redis
     */
    public function redis()
    {
        return $this->redis;
    }
    
    /**
     * 获取缓存器版本
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->redis->info()['redis_version'];
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
        $cacheValue = $this->redis->get($id);
        $ttl        = false;
        
        if ($cacheValue !== false)
        {
            $cacheValue = unserialize($cacheValue);
            $ttl        = $cacheValue['ttl'];
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
     * @return Redis
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $cacheValue = [
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ];
        
        if ($cacheValue['ttl'] != 0)
        {
            $this->redis->set($id, serialize($cacheValue), $cacheValue['ttl']);
        }
        else
        {
            $this->redis->set($id, serialize($cacheValue));
        }
        
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
        $cacheValue = $this->redis->get($id);
        $value      = false;
        
        if ($cacheValue !== false)
        {
            $cacheValue = unserialize($cacheValue);
            $value      = $cacheValue['value'];
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
        $cacheValue = $this->redis->get($id);
        
        return ($cacheValue !== false);
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Redis
     */
    public function deleteItem($id)
    {
        $this->redis->delete($id);
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Redis
     */
    public function clear()
    {
        $this->redis->flushDb();
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return Redis
     */
    public function destroy()
    {
        $this->redis->flushDb();
        $this->redis = null;
        
        return $this;
    }
    
}
