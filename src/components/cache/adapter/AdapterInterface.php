<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cache\adapter;

/**
 * 缓存接口
 *
 * @package eiu\components\cache\adapter
 */
interface AdapterInterface
{
    
    /**
     * 设置全局过期时间
     *
     * @param  int $ttl
     *
     * @return AdapterInterface
     */
    public function setTtl($ttl);
    
    /**
     * 获取全局过期时间
     *
     * @return int
     */
    public function getTtl();
    
    /**
     * 获取指定缓存过期时间
     *
     * @param  string $id
     *
     * @return int
     */
    public function getItemTtl($id);
    
    /**
     * 写入一个缓存
     *
     * @param  string $id
     * @param  mixed  $value
     * @param  int    $ttl
     *
     * @return void
     */
    public function saveItem($id, $value, $ttl = null);
    
    /**
     * 获取指定缓存
     *
     * @param  string $id
     *
     * @return mixed
     */
    public function getItem($id);
    
    /**
     * 判断指定缓存是否存在
     *
     * @param  string $id
     *
     * @return boolean
     */
    public function hasItem($id);
    
    /**
     * 删除指定缓存
     *
     * @param  string $id
     *
     * @return void
     */
    public function deleteItem($id);
    
    /**
     * 清除所有缓存
     *
     * @return void
     */
    public function clear();
    
    /**
     * 销毁缓存器
     *
     * @return void
     */
    public function destroy();
    
}
