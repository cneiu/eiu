<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache;


use ArrayAccess;
use eiu\components\cache\adapter\AdapterInterface;
use eiu\components\cache\adapter\Apc;
use eiu\components\cache\adapter\File;
use eiu\components\cache\adapter\Memcache;
use eiu\components\cache\adapter\Memcached;
use eiu\components\cache\adapter\Redis;
use eiu\components\cache\adapter\Session;
use eiu\components\cache\adapter\Sqlite;
use eiu\components\Component;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;


/**
 * 缓存组件
 *
 * @package eiu\components\cache
 */
class CacheComponent extends Component implements ArrayAccess
{
    /**
     * Cache adapter
     *
     * @var AdapterInterface
     */
    protected $adapter = null;
    
    /**
     * Constructor
     *
     * Instantiate the cache object
     *
     * @param Application $app
     */
    public function __construct(Application $app, ConfigProvider $config, AdapterInterface $adapter = null)
    {
        parent::__construct($app);
        
        if ($adapter)
        {
            $this->setupAdapter($adapter);
        }
        else
        {
            if (isset($config['cache']['TYPE']))
            {
                $ttl = $config['cache'][$config['cache']['TYPE']]['TTL'] ?? 0;
                
                switch ($config['cache']['TYPE'])
                {
                    case 'APC':
                        $this->setupAdapter(new Apc($ttl));
                        break;
                    
                    case 'FILE':
                        $dir = $config['cache'][$config['cache']['TYPE']]['DIR'];
                        
                        if (!$dir or !file_exists($dir) or !is_writeable($dir))
                        {
                            throw new \Exception("Cant write to cache directory\"{$dir}\"");
                        }
                        
                        $this->setupAdapter(new File($dir, $ttl));
                        break;
                    
                    case 'MEMCACHE':
                        $host = $config['cache'][$config['cache']['TYPE']]['HOST'];
                        $port = $config['cache'][$config['cache']['TYPE']]['PORT'];
                        $this->setupAdapter(new Memcache($ttl, $host, $port));
                        break;
                    
                    case 'MEMCACHED':
                        $servers = $config['cache'][$config['cache']['TYPE']]['SERVERS'];
                        $this->setupAdapter(new Memcached($ttl, $servers));
                        break;
                    
                    case 'REDIS':
                        $host = $config['cache'][$config['cache']['TYPE']]['HOST'];
                        $port = $config['cache'][$config['cache']['TYPE']]['PORT'];
                        $this->setupAdapter(new Redis($ttl, $host, $port));
                        break;
                    
                    case 'SQLITE':
                        $file  = $config['cache'][$config['cache']['TYPE']]['FILE'];
                        $table = $config['cache'][$config['cache']['TYPE']]['TABLE'];
                        $pdo   = $config['cache'][$config['cache']['TYPE']]['PDO'];
                        $this->setupAdapter(new Sqlite($file, $ttl, $table, $pdo));
                        break;
                    
                    case 'SESSION':
                    default:
                        $this->setupAdapter(new Session($ttl));
                        break;
                }
            }
        }
        
        if (!$this->adapter)
        {
            throw new \Exception("Undefined cache adapter");
        }
    }
    
    /**
     * 设置适配器
     *
     * @param AdapterInterface $adapter
     */
    public function setupAdapter(Adapter\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * 判断是否设置了适配器
     *
     * @param  string $adapter
     *
     * @return boolean
     */
    public function isAvailable($adapter)
    {
        $adapter  = strtolower($adapter);
        $adapters = $this->getAvailableAdapters();
        
        return (isset($adapters[$adapter]) && ($adapters[$adapter]));
    }
    
    /**
     * 获取可用适配器列表
     *
     * @return array
     */
    public function getAvailableAdapters()
    {
        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];
        
        return [
            'apc'       => (function_exists('apc_cache_info')),
            'file'      => true,
            'memcached' => (class_exists('Memcache', false)),
            'redis'     => (class_exists('Redis', false)),
            'session'   => (function_exists('session_start')),
            'sqlite'    => (class_exists('Sqlite3') || in_array('sqlite', $pdoDrivers)),
        ];
    }
    
    /**
     * 获取当前适配器
     *
     * @return mixed
     */
    public function adapter()
    {
        return $this->adapter;
    }
    
    /**
     * 获取全局缓存过期时间
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->adapter->getTtl();
    }
    
    /**
     * 获取指定缓存的过期时间
     *
     * @param  string $id
     *
     * @return int
     */
    public function getItemTtl($id)
    {
        return $this->adapter->getItemTtl($id);
    }
    
    /**
     * 写入一个缓存
     *
     * @param  string $id
     * @param  mixed  $value
     * @param  int    $ttl
     *
     * @return CacheComponent
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $this->adapter->saveItem($id, $value, $ttl);
        
        return $this;
    }
    
    /**
     * 写入多个缓存
     *
     * @param  array $items
     *
     * @return CacheComponent
     */
    public function saveItems(array $items)
    {
        foreach ($items as $id => $value)
        {
            $this->adapter->saveItem($id, $value);
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
        return $this->adapter->getItem($id);
    }
    
    /**
     * 判断指定缓存是否存在
     *
     * @param  string $id
     *
     * @return mixed
     */
    public function hasItem($id)
    {
        return $this->adapter->hasItem($id);
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return CacheComponent
     */
    public function deleteItem($id)
    {
        $this->adapter->deleteItem($id);
        
        return $this;
    }
    
    /**
     * 删除多个缓存
     *
     * @param  array $ids
     *
     * @return CacheComponent
     */
    public function deleteItems(array $ids)
    {
        foreach ($ids as $id)
        {
            $this->adapter->deleteItem($id);
        }
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return CacheComponent
     */
    public function clear()
    {
        $this->adapter->clear();
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return CacheComponent
     */
    public function destroy()
    {
        $this->adapter->destroy();
        
        return $this;
    }
    
    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }
    
    /**
     * Determine if the item is in cache
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->adapter->hasItem($name);
    }
    
    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
    
    /**
     * Magic get method to return an item from cache
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->adapter->getItem($name);
    }
    
    /**
     * Magic set method to save an item in the cache
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        $this->adapter->saveItem($name, $value);
    }
    
    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }
    
    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }
    
    /**
     * Delete value from cache
     *
     * @param  string $name
     *
     * @throws Exception
     * @return void
     */
    public function __unset($name)
    {
        $this->adapter->deleteItem($name);
    }
}