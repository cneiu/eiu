<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\logger\writer;

/**
 * Log writer interface
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
