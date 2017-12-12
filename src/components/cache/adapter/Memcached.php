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
 * Memcached 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Memcached extends AbstractAdapter
{
    
    /**
     * Memcached object
     *
     * @var \Memcached
     */
    protected $memcached = null;
    
    /**
     * Memcached version
     *
     * @var string
     */
    protected $version = null;
    
    /**
     * Constructor
     *
     * Instantiate the memcached cache object
     *
     * @param  int    $ttl
     * @param  string $host
     * @param  int    $port
     * @param  int    $weight
     *
     * @throws Exception
     */
    public function __construct(int $ttl = 0, array $servers = [])
    {
        parent::__construct($ttl);
        
        if (!class_exists('Memcached', false))
        {
            throw new CacheException('Memcached is not available');
        }
        
        if (!$servers)
        {
            $servers = [['localhost', 11211, 1]];
        }
        
        $this->memcached = new \Memcached();
        $this->addServers($servers);
        
        $version = $this->memcached->getVersion();
        
        if (isset($version[$host . ':' . $port]))
        {
            $this->version = $version[$host . ':' . $port];
        }
    }
    
    /**
     * 增加缓存服务器
     *
     * @param  string $host
     * @param  int    $port
     * @param  int    $weight
     *
     * @return Memcached
     */
    public function addServer($host, $port = 11211, $weight = 1)
    {
        $this->memcached->addServer($host, $port, $weight);
        
        return $this;
    }
    
    /**
     * 增加缓存服务器列表
     *
     * @param  array $servers
     *
     * @return Memcached
     */
    public function addServers(array $servers)
    {
        $this->memcached->addServers($servers);
        
        return $this;
    }
    
    /**
     * 获取缓存器
     *
     * @return \Memcached
     */
    public function memcached()
    {
        return $this->memcached;
    }
    
    /**
     * 获取缓存器版本
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
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
        $cacheValue = $this->memcached->get($id);
        $ttl        = false;
        
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
     * @return Memcached
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $cacheValue = [
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ];
        
        $this->memcached->add($id, $cacheValue, $cacheValue['ttl']);
        
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
        $cacheValue = $this->memcached->get($id);
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
        $cacheValue = $this->memcached->get($id);
        
        return ($cacheValue !== false);
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Memcached
     */
    public function deleteItem($id)
    {
        $this->memcached->delete($id);
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Memcached
     */
    public function clear()
    {
        $this->memcached->flush();
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return Memcached
     */
    public function destroy()
    {
        $this->memcached->flush();
        $this->memcached = null;
        
        return $this;
    }
    
}
