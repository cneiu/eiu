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


use eiu\components\image\AbstractEditObject;


/**
 * Layer abstract class
 */
abstract class AbstractLayer extends AbstractEditObject implements LayerInterface
{
    
    /**
     * Opacity
     *
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
     * Set the image opacity.
     *
     * @param  int $opacity
     *
     * @return AbstractLayer
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
        
        return $this;
    }
    
}
