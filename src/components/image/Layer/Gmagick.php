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


namespace eiu\components\image\Layer;

/**
 * Layer class for Gmagick
 */
class Gmagick extends AbstractLayer
{
    
    /**
     * Opacity
     *
     * @var mixed
     */
    protected $opacity = 1.0;
    
    /**
     * Overlay style
     *
     * @var int
     */
    protected $overlay = \Gmagick::COMPOSITE_ATOP;
    
    /**
     * Get the overlay
     *
     * @return int
     */
    public function getOverlay()
    {
        return $this->overlay;
    }
    
    /**
     * Get the overlay
     *
     * @param  int $overlay
     *
     * @return Gmagick
     */
    public function setOverlay($overlay)
    {
        $this->overlay = $overlay;
        
        return $this;
    }
    
    /**
     * Set the opacity
     *
     * @param  float $opacity
     *
     * @return Gmagick
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
        
        return $this;
    }
    
    /**
     * Overlay an image onto the current image.
     *
     * @param  string $image
     * @param  int    $x
     * @param  int    $y
     *
     * @return Gmagick
     */
    public function overlay($image, $x = 0, $y = 0)
    {
        $overlayImage = new \Gmagick($image);
        $this->image->getResource()->compositeimage($overlayImage, $this->overlay, $x, $y);
        
        return $this;
    }
    
    /**
     * Flatten the image layers
     *
     * @return Gmagick
     */
    public function flatten()
    {
        if (method_exists($this->image->getResource(), 'flattenImages'))
        {
            $this->image->getResource()->flattenimages();
        }
        
        return $this;
    }
    
}
