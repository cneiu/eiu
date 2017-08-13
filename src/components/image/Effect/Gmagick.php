<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */

/**
 * @namespace
 */
namespace eiu\components\image\Effect;

use eiu\components\image\Color;

/**
 * Effect class for Gmagick
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gmagick extends AbstractEffect
{

    /**
     * Draw a border around the image.
     *
     * @param  Color\ColorInterface $color
     * @param  int                  $w
     * @param  int                  $h
     * @throws Exception
     * @return Gmagick
     */
    public function border(Color\ColorInterface $color, $w = 1, $h = null)
    {
        $h = (null === $h) ? $w : $h;
        $this->image->getResource()->borderImage($this->image->createColor($color), $w, $h);
        return $this;
    }

    /**
     * Flood the image with a color fill.
     *
     * @param  Color\ColorInterface $color
     * @return Gmagick
     */
    public function fill(Color\ColorInterface $color)
    {
        $draw = new \GmagickDraw();
        $draw->setfillcolor($this->image->createColor($color));
        $draw->rectangle(0, 0, $this->image->getWidth(), $this->image->getHeight());
        $this->image->getResource()->drawImage($draw);
        return $this;
    }

}
