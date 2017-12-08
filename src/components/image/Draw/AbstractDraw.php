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


namespace eiu\components\image\Draw;


use eiu\components\image\AbstractEditObject;
use eiu\components\image\Color;


/**
 * Draw abstract class
 */
abstract class AbstractDraw extends AbstractEditObject implements DrawInterface
{
    
    /**
     * Opacity
     *
     * @var mixed
     */
    protected $opacity = null;
    
    /**
     * Fill color
     *
     * @var Color\Colorinterface
     */
    protected $fillColor = null;
    
    /**
     * Stroke color
     *
     * @var Color\Colorinterface
     */
    protected $strokeColor = null;
    
    /**
     * Stroke width
     *
     * @var int
     */
    protected $strokeWidth = 0;
    
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
     * Set the opacity
     *
     * @param  float $opacity
     *
     * @return Gmagick
     */
    abstract public function setOpacity($opacity);
    
    /**
     * Get fill color
     *
     * @return Color\Colorinterface
     */
    public function getFillColor()
    {
        return $this->fillColor;
    }
    
    /**
     * Set fill color
     *
     * @param  Color\ColorInterface $color
     *
     * @return AbstractDraw
     */
    public function setFillColor(Color\ColorInterface $color)
    {
        $this->fillColor = $color;
        
        return $this;
    }
    
    /**
     * Get stroke color
     *
     * @return Color\Colorinterface
     */
    public function getStrokeColor()
    {
        return $this->strokeColor;
    }
    
    /**
     * Set stroke color
     *
     * @param  Color\ColorInterface $color
     *
     * @return AbstractDraw
     */
    public function setStrokeColor(Color\ColorInterface $color)
    {
        $this->strokeColor = $color;
        
        return $this;
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
     * Get stroke width
     *
     * @param int $w
     *
     * @return AbstractDraw
     */
    public function setStrokeWidth($w)
    {
        $this->strokeWidth = (int)$w;
        
        return $this;
    }
    
}
