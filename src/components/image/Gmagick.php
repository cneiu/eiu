<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;

/**
 * Gmagick 工厂类
 *
 * @package eiu\components\image
 */
class Gmagick
{
    /**
     * 加载图像文件
     *
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public static function load($image)
    {
        return new Adapter\Gmagick($image);
    }
    
    /**
     * 加载图像数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Gmagick
     */
    public static function loadFromString($data, $name = null)
    {
        $gmagick = new Adapter\Gmagick();
        $gmagick->loadFromString($data, $name);
        
        return $gmagick;
    }
    
    /**
     * 创建图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public static function create($width, $height, $image = null)
    {
        return new Adapter\Gmagick($width, $height, $image);
    }
    
    /**
     * 创建索引图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public static function createIndex($width, $height, $image = null)
    {
        $gmagick = new Adapter\Gmagick();
        $gmagick->createIndex($width, $height, $image);
        
        return $gmagick;
    }
    
}