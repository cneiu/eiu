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
 * Type class for Gd
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gd extends AbstractType
{

    /**
     * Opacity
     * @var int
     */
    protected $opacity = 0;

    /**
     * Set the opacity
     *
     * @param  int $opacity
     * @return Gd
     */
    public function setOpacity($opacity)
    {
        $this->opacity = (int)round((127 - (127 * ($opacity / 100))));
        return $this;
    }

    /**
     * Set and apply the text on the image
     *
     * @param  string $string
     * @return Gd
     */
    public function text($string)
    {
        $fillColor = ($this->image->isIndexed()) ? $this->image->createColor($this->fillColor, null) :
            $this->image->createColor($this->fillColor, $this->opacity);

        if ((null !== $this->font) && function_exists('imagettftext')) {
            if (null !== $this->strokeColor) {
                $strokeColor = ($this->image->isIndexed()) ? $this->image->createColor($this->strokeColor, null) :
                    $this->image->createColor($this->strokeColor, $this->opacity);
                imagettftext($this->image->getResource(), $this->size, $this->rotation, $this->x, ($this->y - 1), $strokeColor, $this->font, $string);
                imagettftext($this->image->getResource(), $this->size, $this->rotation, $this->x, ($this->y + 1), $strokeColor, $this->font, $string);
                imagettftext($this->image->getResource(), $this->size, $this->rotation, ($this->x - 1), $this->y, $strokeColor, $this->font, $string);
                imagettftext($this->image->getResource(), $this->size, $this->rotation, ($this->x + 1), $this->y, $strokeColor, $this->font, $string);
            }
            imagettftext($this->image->getResource(), $this->size, $this->rotation, $this->x, $this->y, $fillColor, $this->font, $string);
        } else {
            // Cap the system font size between 1 and 5
            if ($this->size > 5) {
                $this->size = 5;
            } else if ($this->size < 1) {
                $this->size = 1;
            }
            imagestring($this->image->getResource(), $this->size, $this->x, $this->y,  $string, $fillColor);
        }

        return $this;
    }

}
