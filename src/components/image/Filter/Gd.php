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
namespace eiu\components\image\Filter;

use eiu\components\image\Color;

/**
 * Filter class for Gd
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gd extends AbstractFilter
{

    /**
     * Blur the image
     *
     * @param  int $amount
     * @param  int $type
     * @return Gd
     */
    public function blur($amount, $type = IMG_FILTER_GAUSSIAN_BLUR)
    {
        for ($i = 1; $i <= $amount; $i++) {
            imagefilter($this->image->getResource(), $type);
        }

        return $this;
    }

    /**
     * Sharpen the image.
     *
     * @param  int $amount
     * @return Gd
     */
    public function sharpen($amount)
    {
        imagefilter($this->image->getResource(), IMG_FILTER_SMOOTH, (0 - $amount));
        return $this;
    }

    /**
     * Create a negative of the image
     *
     * @return Gd
     */
    public function negate()
    {
        imagefilter($this->image->getResource(), IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Colorize the image
     *
     * @param  Color\Rgb $color
     * @return Gd
     */
    public function colorize(Color\Rgb $color)
    {
        imagefilter($this->image->getResource(), IMG_FILTER_COLORIZE, $color->getR(), $color->getG(), $color->getB());
        return $this;
    }

    /**
     * Pixelate the image
     *
     * @param  int $px
     * @return Gd
     */
    public function pixelate($px)
    {
        imagefilter($this->image->getResource(), IMG_FILTER_PIXELATE, $px, true);
        return $this;
    }

    /**
     * Apply a pencil/sketch effect to the image
     *
     * @return Gd
     */
    public function pencil()
    {
        imagefilter($this->image->getResource(), IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

}
