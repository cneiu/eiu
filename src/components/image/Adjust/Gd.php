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
namespace eiu\components\image\Adjust;

/**
 * Adjust class for Gd
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gd extends AbstractAdjust
{

    /**
     * Adjust the image brightness
     *
     * @param  int $amount
     * @return Gd
     */
    public function brightness($amount)
    {
        imagefilter($this->image->getResource(), IMG_FILTER_BRIGHTNESS, $amount);
        return $this;
    }

    /**
     * Adjust the image contrast
     *
     * @param  int $amount
     * @return Gd
     */
    public function contrast($amount)
    {
        imagefilter($this->image->getResource(), IMG_FILTER_CONTRAST, (0 - $amount));
        return $this;
    }

    /**
     * Adjust the image desaturate
     *
     * @return Gd
     */
    public function desaturate()
    {
        imagefilter($this->image->getResource(), IMG_FILTER_GRAYSCALE);
        return $this;
    }

}
