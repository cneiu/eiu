<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;

/**
 * Imagick 工厂类
 *
 * @package eiu\components\image
 */
class Imagick
{
    /**
     * 加载图形文件
     *
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public static function load($image)
    {
        return new Adapter\Imagick($image);
    }
    
    /**
     * 加载图形数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Imagick
     */
    public static function loadFromString($data, $name = null)
    {
        $imagick = new Adapter\Imagick();
        $imagick->loadFromString($data, $name);
        
        return $imagick;
    }
    
    /**
     * 创建图形
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public static function create($width, $height, $image = null)
    {
        return new Adapter\Imagick($width, $height, $image);
    }
    
    /**
     * 创建索引图形
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public static function createIndex($width, $height, $image = null)
    {
        $imagick = new Adapter\Imagick();
        $imagick->createIndex($width, $height, $image);
        
        return $imagick;
    }
    
}