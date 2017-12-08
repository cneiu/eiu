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
 * Abstract image color class
 */
abstract class AbstractColor implements ColorInterface
{
    
    /**
     * Method to print the color object
     *
     * @return string
     */
    abstract public function __toString();
    
}