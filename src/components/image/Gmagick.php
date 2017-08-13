<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;

/**
 * Image Gmagick factory class
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gmagick
{

    /**
     * Get the available image adapters
     *
     * @return array
     */
    public static function getAvailableAdapters()
    {
        return Image::getAvailableAdapters();
    }

    /**
     * Determine if the Gmagick adapter is available
     *
     * @return boolean
     */
    public static function isAvailable()
    {
        return Image::isAvailable('gmagick');
    }

    /**
     * Load the image resource from the existing image file into a Gmagick object
     *
     * @param  string $image
     * @return Adapter\Gmagick
     */
    public static function load($image)
    {
        return new Adapter\Gmagick($image);
    }

    /**
     * Load the image resource from data into a Gmagick object
     *
     * @param  string $data
     * @param  string $name
     * @return Adapter\Gmagick
     */
    public static function loadFromString($data, $name = null)
    {
        $gmagick = new Adapter\Gmagick();
        $gmagick->loadFromString($data, $name);
        return $gmagick;
    }

    /**
     * Create a new image resource and load it into a Gmagick object
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     * @return Adapter\Gmagick
     */
    public static function create($width, $height, $image = null)
    {
        return new Adapter\Gmagick($width, $height, $image);
    }

    /**
     * Create a new indexed image resource and load it into a Gmagick object
     *
     * @param  int    $width
     * @param  int    $height
     * @param  string $image
     * @return Adapter\Gmagick
     */
    public static function createIndex($width, $height, $image = null)
    {
        $gmagick = new Adapter\Gmagick();
        $gmagick->createIndex($width, $height, $image);
        return $gmagick;
    }

}