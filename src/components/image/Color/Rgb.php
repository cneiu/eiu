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


namespace eiu\components\image\Color;

/**
 * Image RGB color class
 */
class Rgb extends AbstractColor
{
    
    /**
     * Red
     *
     * @var float
     */
    protected $r = 0;
    
    /**
     * Green
     *
     * @var float
     */
    protected $g = 0;
    
    /**
     * Blue
     *
     * @var float
     */
    protected $b = 0;
    
    /**
     * Constructor
     *
     * Instantiate a PDF RGB Color object
     *
     * @param  mixed $r 0 - 255
     * @param  mixed $g 0 - 255
     * @param  mixed $b 0 - 255
     */
    public function __construct($r, $g, $b)
    {
        $this->setR($r);
        $this->setG($g);
        $this->setB($b);
    }
    
    /**
     * Get the red value
     *
     * @return float
     */
    public function getR()
    {
        return $this->r;
    }
    
    /**
     * Set the red value
     *
     * @param  mixed $r
     *
     * @throws \OutOfRangeException
     * @return Rgb
     */
    public function setR($r)
    {
        if (((int)$r < 0) || ((int)$r > 255))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 255');
        }
        $this->r = (int)$r;
        
        return $this;
    }
    
    /**
     * Get the green value
     *
     * @return float
     */
    public function getG()
    {
        return $this->g;
    }
    
    /**
     * Set the green value
     *
     * @param  mixed $g
     *
     * @throws \OutOfRangeException
     * @return Rgb
     */
    public function setG($g)
    {
        if (((int)$g < 0) || ((int)$g > 255))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 255');
        }
        $this->g = (int)$g;
        
        return $this;
    }
    
    /**
     * Get the blue value
     *
     * @return float
     */
    public function getB()
    {
        return $this->b;
    }
    
    /**
     * Set the blue value
     *
     * @param  mixed $b
     *
     * @throws \OutOfRangeException
     * @return Rgb
     */
    public function setB($b)
    {
        if (((int)$b < 0) || ((int)$b > 255))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 255');
        }
        $this->b = (int)$b;
        
        return $this;
    }
    
    /**
     * Convert to CMYK
     *
     * @return Cmyk
     */
    public function toCmyk()
    {
        $K = 1;
        
        // Calculate CMY.
        $cyan    = 1 - ($this->r / 255);
        $magenta = 1 - ($this->g / 255);
        $yellow  = 1 - ($this->b / 255);
        
        // Calculate K.
        if ($cyan < $K)
        {
            $K = $cyan;
        }
        if ($magenta < $K)
        {
            $K = $magenta;
        }
        if ($yellow < $K)
        {
            $K = $yellow;
        }
        
        if ($K == 1)
        {
            $cyan    = 0;
            $magenta = 0;
            $yellow  = 0;
        }
        else
        {
            $cyan    = round((($cyan - $K) / (1 - $K)) * 100);
            $magenta = round((($magenta - $K) / (1 - $K)) * 100);
            $yellow  = round((($yellow - $K) / (1 - $K)) * 100);
        }
        
        $black = round($K * 100);
        
        return new Cmyk($cyan, $magenta, $yellow, $black);
    }
    
    /**
     * Convert to Gray
     *
     * @return Gray
     */
    public function toGray()
    {
        return new Gray(floor(($this->r + $this->g + $this->b) / 3));
    }
    
    /**
     * Convert to hex string
     *
     * @return string
     */
    public function toHex()
    {
        return sprintf('%02x', $this->r) . sprintf('%02x', $this->g) . sprintf('%02x', $this->b);
    }
    
    /**
     * Method to print the color object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->r . ', ' . $this->g . ', ' . $this->b;
    }
    
}