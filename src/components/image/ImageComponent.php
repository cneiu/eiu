<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;


/**
 * 图像处理组件
 *
 * @package eiu\components\image
 */
class ImageComponent
{
    
    /**
     * 获取所有适配器列表
     *
     * @return array
     */
    public static function getAvailableAdapters()
    {
        return [
            'gd'      => function_exists('gd_info'),
            'gmagick' => (class_exists('Gmagick', false)),
            'imagick' => (class_exists('Imagick', false)),
        ];
    }
    
    /**
     * 判断指定适配器是否可用
     *
     * @param  string $adapter
     *
     * @return boolean
     */
    public static function isAvailable($adapter)
    {
        $result = false;
        
        switch (strtolower($adapter))
        {
            case 'gd':
                $result = function_exists('gd_info');
                break;
            case 'graphicsmagick':
            case 'gmagick':
                $result = (class_exists('Gmagick', false));
                break;
            case 'imagemagick':
            case 'imagick':
                $result = (class_exists('Imagick', false));
                break;
        }
        
        return $result;
    }
    
    /**
     * 使用 GD 适配器加载图像文件
     *
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public function loadGd($image)
    {
        return Gd::load($image);
    }
    
    /**
     * 使用 Gmagick 适配器加载图像文件
     *
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public function loadGmagick($image)
    {
        return Gmagick::load($image);
    }
    
    /**
     * 使用 Imagick 适配器加载图像文件
     *
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public function loadImagick($image)
    {
        return Imagick::load($image);
    }
    
    /**
     * 使用 GD 适配器加载图像数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Gd
     */
    public function loadGdFromString($data, $name = null)
    {
        return Gd::loadFromString($data, $name);
    }
    
    /**
     * 使用 Gmagick 适配器加载图像数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Gmagick
     */
    public function loadGmagickFromString($data, $name = null)
    {
        return Gmagick::loadFromString($data, $name);
    }
    
    /**
     * 使用 Imagick 适配器加载图像数据
     *
     * @param  string $data
     * @param  string $name
     *
     * @return Adapter\Imagick
     */
    public function loadImagickFromString($data, $name = null)
    {
        return Imagick::loadFromString($data, $name);
    }
    
    /**
     * 使用 GD 适配器创建图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public function createGd($width, $height, $image = null)
    {
        return Gd::create($width, $height, $image);
    }
    
    /**
     * 使用 GD 适配器创建索引图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gd
     */
    public function createGdIndex($width, $height, $image = null)
    {
        return Gd::createIndex($width, $height, $image);
    }
    
    /**
     * 使用 Gmagick 适配器创建图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public function createGmagick($width, $height, $image = null)
    {
        return Gmagick::create($width, $height, $image);
    }
    
    /**
     * 使用 Gmagick 适配器创建索引图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Gmagick
     */
    public function createGmagickIndex($width, $height, $image = null)
    {
        return Gmagick::createIndex($width, $height, $image);
    }
    
    /**
     * 使用 Imagick 适配器创建图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public function createImagick($width, $height, $image = null)
    {
        return Imagick::create($width, $height, $image);
    }
    
    /**
     * 使用 Imagick 适配器创建索引图像
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     *
     * @return Adapter\Imagick
     */
    public function createImagickIndex($width, $height, $image = null)
    {
        return Imagick::createIndex($width, $height, $image);
    }
    
}