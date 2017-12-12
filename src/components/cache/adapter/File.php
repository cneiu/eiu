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
 * 文件缓存适配器
 *
 * @package eiu\components\cache\adapter
 */
class File extends AbstractAdapter
{
    
    /**
     * Cache dir
     *
     * @var string
     */
    protected $dir = null;
    
    /**
     * Constructor
     *
     * Instantiate the cache file object
     *
     * @param  string $dir
     * @param  int    $ttl
     */
    public function __construct($dir, $ttl = 0)
    {
        parent::__construct($ttl);
        $this->setDir($dir);
    }
    
    /**
     * 获取缓存目录
     *
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }
    
    /**
     * 设置缓存目录
     *
     * @param  string $dir
     *
     * @throws Exception
     * @return File
     */
    public function setDir($dir)
    {
        if (!file_exists($dir))
        {
            if (!@mkdir($dir, 0777, true))
            {
                throw new CacheException('That cache directory does not exist');
            }
        }
        else if (!is_writable($dir))
        {
            throw new CacheException('That cache directory is not writable');
        }
        
        $this->dir = realpath($dir);
        
        return $this;
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
        $fileId = $this->dir . DIRECTORY_SEPARATOR . sha1($id);
        $ttl    = 0;
        
        if (file_exists($fileId))
        {
            $cacheValue = unserialize(file_get_contents($fileId));
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
     * @return File
     */
    public function saveItem($id, $value, $ttl = null)
    {
        file_put_contents($this->dir . DIRECTORY_SEPARATOR . sha1($id), serialize([
            'start' => time(),
            'ttl'   => (null !== $ttl) ? (int)$ttl : $this->ttl,
            'value' => $value,
        ]));
        
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
        $fileId = $this->dir . DIRECTORY_SEPARATOR . sha1($id);
        $value  = false;
        
        if (file_exists($fileId))
        {
            $cacheValue = unserialize(file_get_contents($fileId));
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
     * @return File
     */
    public function deleteItem($id)
    {
        $fileId = $this->dir . DIRECTORY_SEPARATOR . sha1($id);
        if (file_exists($fileId))
        {
            unlink($fileId);
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
        $fileId = $this->dir . DIRECTORY_SEPARATOR . sha1($id);
        $result = false;
        
        if (file_exists($fileId))
        {
            $cacheValue = unserialize(file_get_contents($fileId));
            $result     = (($cacheValue['ttl'] == 0) || ((time() - $cacheValue['start']) <= $cacheValue['ttl']));
        }
        
        return $result;
    }
    
    /**
     * 销毁缓存器
     *
     * @return File
     */
    public function destroy()
    {
        $this->clear();
        @rmdir($this->dir);
        
        return $this;
    }
    
    /**
     * 清除所有缓存
     *
     * @return File
     */
    public function clear()
    {
        if (!$dh = @opendir($this->dir))
        {
            return;
        }
        
        while (false !== ($obj = readdir($dh)))
        {
            if (($obj != '.') && ($obj != '..') &&
                !is_dir($this->dir . DIRECTORY_SEPARATOR . $obj) && is_file($this->dir . DIRECTORY_SEPARATOR . $obj))
            {
                unlink($this->dir . DIRECTORY_SEPARATOR . $obj);
            }
        }
        
        closedir($dh);
        
        return $this;
    }
    
}
