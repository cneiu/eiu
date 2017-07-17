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


namespace eiu\core\service\logger\writer;

/**
 * Log writer interface
 *
 * @category   Pop
 * @package    Pop\Log
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
interface WriterInterface
{
    
    /**
     * Write to the log
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     *
     * @return WriterInterface
     */
    public function writeLog($level, $message, array $context = []);
    
    /**
     * Determine
     *
     * @param  array $context
     *
     * @return string
     */
    public function getContext(array $context = []);
    
}
