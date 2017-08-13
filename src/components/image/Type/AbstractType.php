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
namespace eiu\components\image\Type;

use eiu\components\image\AbstractEditObject;
use eiu\components\image\Color;

/**
 * Type abstract class
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
abstract class AbstractType extends AbstractEditObject implements TypeInterface
{

    /**
     * Type font size
     * @var int
     */
    protected $size = 12;

    /**
     * Type font
     * @var string
     */
    protected $font = null;

    /**
     * Fill color
     * @var Color\ColorInterface
     */
    protected $fillColor = null;

    /**
     * Stroke color
     * @var Color\ColorInterface
     */
    protected $strokeColor = null;

    /**
     * Stroke width
     * @var int
     */
    protected $strokeWidth = 1;

    /**
     * Type X-position
     * @var int
     */
    protected $x = 0;

    /**
     * Type Y-position
     * @var int
     */
    protected $y = 0;

    /**
     * Type rotation in degrees
     * @var int
     */
    protected $rotation = 0;

    /**
     * Opacity
     * @var mixed
     */
    protected $opacity = null;

    /**
     * Get the opacity
     *
     * @return mixed
     */
    public function getOpacity()
    {
        return $this->opacity;
    }

    /**
     * Get fill color
     *
     * @return Color\ColorInterface
     */
    public function getFillColor()
    {
        return $this->fillColor;
    }

    /**
     * Get stroke color
     *
     * @return Color\ColorInterface
     */
    public function getStrokeColor()
    {
        return $this->strokeColor;
    }

    /**
     * Get stroke width
     *
     * @return int
     */
    public function getStrokeWidth()
    {
        return $this->strokeWidth;
    }

    /**
     * Set fill color
     *
     * @param  Color\ColorInterface $color
     * @return AbstractType
     */
    public function setFillColor(Color\ColorInterface $color)
    {
        $this->fillColor = $color;
        return $this;
    }

    /**
     * Set stroke color
     *
     * @param  Color\ColorInterface $color
     * @return AbstractType
     */
    public function setStrokeColor(Color\ColorInterface $color)
    {
        $this->strokeColor = $color;
        return $this;
    }

    /**
     * Set stroke width
     *
     * @param  int $w
     * @return AbstractType
     */
    public function setStrokeWidth($w)
    {
        $this->strokeWidth = $w;
        return $this;
    }

    /**
     * Set the font size
     *
     * @param  int $size
     * @return AbstractType
     */
    public function size($size)
    {
        $this->size = (int)$size;
        return $this;
    }

    /**
     * Set the font
     *
     * @param  string $font
     * @return AbstractType
     */
    public function font($font)
    {
        $this->font = $font;
        return $this;
    }

    /**
     * Set the X-position
     *
     * @param  int $x
     * @return AbstractType
     */
    public function x($x)
    {
        $this->x = (int)$x;
        return $this;
    }

    /**
     * Set the Y-position
     *
     * @param  int $y
     * @return AbstractType
     */
    public function y($y)
    {
        $this->y = (int)$y;
        return $this;
    }

    /**
     * Set both the X- and Y-positions
     *
     * @param  int $x
     * @param  int $y
     * @return AbstractType
     */
    public function xy($x, $y)
    {
        $this->x($x);
        $this->y($y);
        return $this;
    }

    /**
     * Set the rotation of the text
     *
     * @param  int $degrees
     * @return AbstractType
     */
    public function rotate($degrees)
    {
        $this->rotation = (int)$degrees;
        return $this;
    }

    /**
     * Set the opacity
     *
     * @param  mixed $opacity
     * @return AbstractType
     */
    abstract public function setOpacity($opacity);

}
