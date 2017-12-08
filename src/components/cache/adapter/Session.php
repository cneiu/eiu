<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache\adapter;


/**
 * Session 缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class Session extends AbstractAdapter
{
    /**
     * Constructor
     *
     * Instantiate the cache session object
     *
     * @param  int $ttl
     */
    public function __construct($ttl = 0)
    {
        parent::__construct($ttl);
        if (session_id() == '')
        {
            session_start();
        }
        if (!isset($_SESSION['_EIU_CACHE']))
        {
            $_SESSION['_EIU_CACHE'] = [];
        }
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
        $ttl = 0;
        
        if (isset($_SESSION['_EIU_CACHE'][$id]))
        {
            $cacheValue = unserialize($_SESSION['_EIU_CACHE'][$id]);
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
     * @return Session
     */
    public function saveItem($id, $value, $ttl = null)
    {
        $_SESSION['_EIU_CACHE'][$id] = serialize([
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ]);
        
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
        $value = false;
        
        if (isset($_SESSION['_EIU_CACHE'][$id]))
        {
            $cacheValue = unserialize($_SESSION['_EIU_CACHE'][$id]);
            if (($cacheValue['ttl'] == 0) || ((time() - $cacheValue['start']) <= $cacheValue['ttl']))
            {
                $value = $cacheValue['value'];
            }
            else
            {
                $this->deleteItem($id);
            }
        }
        
        return $value;
    }
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return Session
     */
    public function deleteItem($id)
    {
        if (isset($_SESSION['_EIU_CACHE'][$id]))
        {
            unset($_SESSION['_EIU_CACHE'][$id]);
        }
        
        return $this;
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
        $result = false;
        
        if (isset($_SESSION['_EIU_CACHE'][$id]))
        {
            $cacheValue = unserialize($_SESSION['_EIU_CACHE'][$id]);
            $result     = (($cacheValue['ttl'] == 0) || ((time() - $cacheValue['start']) <= $cacheValue['ttl']));
        }
        
        return $result;
    }
    
    /**
     * 清除所有缓存
     *
     * @return Session
     */
    public function clear()
    {
        $_SESSION['_EIU_CACHE'] = [];
        
        return $this;
    }
    
    /**
     * 销毁缓存器
     *
     * @return void
     */
    public function destroy()
    {
        $_SESSION = null;
        session_unset();
        session_destroy();
    }
}
