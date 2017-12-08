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
 * Image CMYK color class
 */
class Cmyk extends AbstractColor
{
    
    /**
     * Cyan
     *
     * @var float
     */
    protected $c = 0;
    
    /**
     * Magenta
     *
     * @var float
     */
    protected $m = 0;
    
    /**
     * Yellow
     *
     * @var float
     */
    protected $y = 0;
    
    /**
     * Black
     *
     * @var float
     */
    protected $k = 0;
    
    /**
     * Constructor
     *
     * Instantiate a PDF CMYK Color object
     *
     * @param  mixed $c 0 - 100
     * @param  mixed $m 0 - 100
     * @param  mixed $y 0 - 100
     * @param  mixed $k 0 - 100
     */
    public function __construct($c, $m, $y, $k)
    {
        $this->setC($c);
        $this->setM($m);
        $this->setY($y);
        $this->setK($k);
    }
    
    /**
     * Get the cyan value
     *
     * @return float
     */
    public function getC()
    {
        return $this->c;
    }
    
    /**
     * Set the cyan value
     *
     * @param  mixed $c
     *
     * @throws \OutOfRangeException
     * @return Cmyk
     */
    public function setC($c)
    {
        if (((int)$c < 0) || ((int)$c > 100))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 100');
        }
        $this->c = (int)$c;
        
        return $this;
    }
    
    /**
     * Get the magenta value
     *
     * @return float
     */
    public function getM()
    {
        return $this->m;
    }
    
    /**
     * Set the magenta value
     *
     * @param  mixed $m
     *
     * @throws \OutOfRangeException
     * @return Cmyk
     */
    public function setM($m)
    {
        if (((int)$m < 0) || ((int)$m > 100))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 100');
        }
        $this->m = (int)$m;
        
        return $this;
    }
    
    /**
     * Get the yellow value
     *
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }
    
    /**
     * Set the yellow value
     *
     * @param  mixed $y
     *
     * @throws \OutOfRangeException
     * @return Cmyk
     */
    public function setY($y)
    {
        if (((int)$y < 0) || ((int)$y > 100))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 100');
        }
        $this->y = (int)$y;
        
        return $this;
    }
    
    /**
     * Get the black value
     *
     * @return float
     */
    public function getK()
    {
        return $this->k;
    }
    
    /**
     * Set the black value
     *
     * @param  mixed $k
     *
     * @throws \OutOfRangeException
     * @return Cmyk
     */
    public function setK($k)
    {
        if (((int)$k < 0) || ((int)$k > 100))
        {
            throw new \OutOfRangeException('Error: The value must be between 0 and 100');
        }
        $this->k = (int)$k;
        
        return $this;
    }
    
    /**
     * Convert to RGB
     *
     * @return Rgb
     */
    public function toRgb()
    {
        // Calculate CMY.
        $c = $this->c / 100;
        $m = $this->m / 100;
        $y = $this->y / 100;
        $k = $this->k / 100;
        
        $cyan    = (($c * (1 - $k)) + $k);
        $magenta = (($m * (1 - $k)) + $k);
        $yellow  = (($y * (1 - $k)) + $k);
        
        // Calculate RGB.
        $r = round((1 - $cyan) * 255);
        $g = round((1 - $magenta) * 255);
        $b = round((1 - $yellow) * 255);
        
        return new Rgb($r, $g, $b);
    }
    
    /**
     * Convert to Gray
     *
     * @return Gray
     */
    public function toGray()
    {
        return new Gray($this->k);
    }
    
    /**
     * Method to print the color object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->c . ', ' . $this->m . ', ' . $this->y . ', ' . $this->k;
    }
    
}