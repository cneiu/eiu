<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\image;

use eiu\components\image\Adapter\AbstractAdapter;

/**
 * Abstract image edit class
 *
 * @category   Pop
 * @package    eiu\components\image
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
abstract class AbstractEditObject
{

    /**
     * Image object
     * @var mixed
     */
    protected $image = null;

    /**
     * Constructor
     *
     * Instantiate an image edit object
     *
     * @param AbstractAdapter $image
     */
    public function __construct(AbstractAdapter $image = null)
    {
        if (null !== $image) {
            $this->setImage($image);
        }
    }

    /**
     * Get the image object
     *
     * @return AbstractAdapter
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the image object
     *
     * @param  AbstractAdapter $image
     * @return AbstractEditObject
     */
    public function setImage(AbstractAdapter $image)
    {
        $this->image = $image;
        return $this;
    }

}
