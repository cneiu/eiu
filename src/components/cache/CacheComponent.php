<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache;


use eiu\components\cache\adapter\AdapterInterface;
use eiu\components\Component;
use eiu\core\application\Application;


/**
 * Cache component
 *
 * @package eiu\components\cache
 */
class CacheComponent extends Component
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
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }
    
    /**
     * setup adapter
     *
     * @param AdapterInterface $adapter
     */
    public function setupAdapter(Adapter\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * Determine if an adapter is available
     *
     * @param  string $adapter
     *
     * @return boolean
     */
    public static function isAvailable($adapter)
    {
        $adapter  = strtolower($adapter);
        $adapters = self::getAvailableAdapters();
        
        return (isset($adapters[$adapter]) && ($adapters[$adapter]));
    }
    
    /**
     * Determine available adapters
     *
     * @return array
     */
    public static function getAvailableAdapters()
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
     * Get the adapter
     *
     * @return mixed
     */
    public function adapter()
    {
        return $this->adapter;
    }
    
    /**
     * Get global cache TTL
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->adapter->getTtl();
    }
    
    /**
     * Get item cache TTL
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
     * Save an item to cache
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
     * Save items to cache
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
     * Get an item from cache
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
     * Determine if the item is in cache
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
     * Delete an item in cache
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
     * Delete items in cache
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
     * Clear all stored values from cache
     *
     * @return CacheComponent
     */
    public function clear()
    {
        $this->adapter->clear();
        
        return $this;
    }
    
    /**
     * Destroy cache resource
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