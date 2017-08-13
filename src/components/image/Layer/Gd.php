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
 * Layer class for Gd
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Gd extends AbstractLayer
{

    /**
     * Opacity
     * @var int
     */
    protected $opacity = 100;

    /**
     * Overlay an image onto the current image.
     *
     * @param  string $image
     * @param  int    $x
     * @param  int    $y
     * @throws Exception
     * @return Gd
     */
    public function overlay($image, $x = 0, $y = 0)
    {
        imagealphablending($this->image->getResource(), true);

        // Create an image resource from the overlay image.
        if (stripos($image, '.gif') !== false) {
            $overlay = imagecreatefromgif($image);
        } else if (stripos($image, '.png') !== false) {
            $overlay = imagecreatefrompng($image);
        } else if (stripos($image, '.jp') !== false) {
            $overlay = imagecreatefromjpeg($image);
        } else {
            throw new Exception('Error: The overlay image must be either a JPG, GIF or PNG.');
        }

        if ($this->opacity > 0) {
            if ($this->opacity == 100) {
                imagecopy($this->image->getResource(), $overlay, $x, $y, 0, 0, imagesx($overlay), imagesy($overlay));
            } else{
                imagecopymerge($this->image->getResource(), $overlay, $x, $y, 0, 0, imagesx($overlay), imagesy($overlay), $this->opacity);
            }
        }

        return $this;
    }

}
