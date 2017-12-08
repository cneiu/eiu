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

/**
 * Type class for Gmagick
 */
class Gmagick extends AbstractType
{
    
    /**
     * Opacity
     *
     * @var float
     */
    protected $opacity = 1.0;
    
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
     * Set and apply the text on the image
     *
     * @param  string $string
     *
     * @throws Exception
     * @return Gmagick
     */
    public function text($string)
    {
        $draw = new \GmagickDraw();
        
        // Set the font if passed
        if (null !== $this->font)
        {
            if (!$draw->setfont($this->font))
            {
                throw new Exception('Error: That font is not recognized by the Gmagick extension.');
            }
            // Else, attempt to set a basic, default system font
        }
        else
        {
            $fonts = $this->image->getResource()->queryFonts();
            if (in_array('Arial', $fonts))
            {
                $this->font = 'Arial';
            }
            else if (in_array('Helvetica', $fonts))
            {
                $this->font = 'Helvetica';
            }
            else if (in_array('Tahoma', $fonts))
            {
                $this->font = 'Tahoma';
            }
            else if (in_array('Verdana', $fonts))
            {
                $this->font = 'Verdana';
            }
            else if (in_array('System', $fonts))
            {
                $this->font = 'System';
            }
            else if (in_array('Fixed', $fonts))
            {
                $this->font = 'Fixed';
            }
            else if (in_array('system', $fonts))
            {
                $this->font = 'system';
            }
            else if (in_array('fixed', $fonts))
            {
                $this->font = 'fixed';
            }
            else if (isset($fonts[0]))
            {
                $this->font = $fonts[0];
            }
            else
            {
                throw new Exception('Error: No default font could be found by the Gmagick extension.');
            }
        }
        
        $draw->setfont($this->font);
        $draw->setfontsize($this->size);
        $draw->setfillcolor($this->image->createColor($this->fillColor, $this->opacity));
        
        if (null !== $this->rotation)
        {
            $draw->rotate($this->rotation);
        }
        
        if (null !== $this->strokeColor)
        {
            $draw->setstrokecolor($this->image->createColor($this->strokeColor, $this->opacity));
            $draw->setstrokewidth((int)$this->strokeWidth);
        }
        
        $draw->annotate($this->x, $this->y, $string);
        $this->image->getResource()->drawImage($draw);
        
        return $this;
    }
    
}
