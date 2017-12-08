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
 * Adjust class for Gmagick
 */
class Gmagick extends AbstractAdjust
{
    
    /**
     * Method to adjust the hue of the image.
     *
     * @param  int $amount
     *
     * @return Gmagick
     */
    public function hue($amount)
    {
        $this->image->getResource()->modulateimage(100, 100, $amount);
        
        return $this;
    }
    
    /**
     * Method to adjust the saturation of the image.
     *
     * @param  int $amount
     *
     * @return Gmagick
     */
    public function saturation($amount)
    {
        $this->image->getResource()->modulateimage(100, $amount, 100);
        
        return $this;
    }
    
    /**
     * Adjust the image brightness
     *
     * @param  int $amount
     *
     * @return Gmagick
     */
    public function brightness($amount)
    {
        $this->image->getResource()->modulateimage($amount, 100, 100);
        
        return $this;
    }
    
    /**
     * Method to adjust the HSB of the image altogether.
     *
     * @param  int $h
     * @param  int $s
     * @param  int $b
     *
     * @return Gmagick
     */
    public function hsb($h, $s, $b)
    {
        $this->image->getResource()->modulateimage($h, $s, $b);
        
        return $this;
    }
    
    /**
     * Method to adjust the levels of the image using a 0 - 255 range.
     *
     * @param  int   $black
     * @param  float $gamma
     * @param  int   $white
     *
     * @return Gmagick
     */
    public function level($black, $gamma, $white)
    {
        $this->image->getResource()->levelimage($black, $gamma, $white);
        
        return $this;
    }
    
    /**
     * Adjust the image contrast
     *
     * @param  int $amount
     *
     * @return Gmagick
     */
    public function contrast($amount)
    {
        if ($amount > 0)
        {
            for ($i = 1; $i <= $amount; $i++)
            {
                $this->image->getResource()->normalizeimage(\Gmagick::CHANNEL_ALL);
            }
        }
        
        return $this;
    }
    
    /**
     * Adjust the image desaturate
     *
     * @return Gmagick
     */
    public function desaturate()
    {
        $this->image->getResource()->modulateimage(100, 0, 100);
        
        return $this;
    }
    
}
