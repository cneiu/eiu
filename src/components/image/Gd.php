<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;

/**
 * Gd 工厂类
 *
 * @package eiu\components\image
 */
class Gd
{
    /**
     * 加载图形文件
     *
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public static function load($image)
    {
        return new Adapter\Gd($image);
    }
    
    /**
     * 加载图形数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Gd
     */
    public static function loadFromString($data, $name = null)
    {
        $gd = new Adapter\Gd();
        $gd->loadFromString($data, $name);
        
        return $gd;
    }
    
    /**
     * 创建图形
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public static function create($width, $height, $image = null)
    {
        return new Adapter\Gd($width, $height, $image);
    }
    
    /**
     * 创建索引图形
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public static function createIndex($width, $height, $image = null)
    {
        $gd = new Adapter\Gd();
        $gd->createIndex($width, $height, $image);
        
        return $gd;
    }
    
}