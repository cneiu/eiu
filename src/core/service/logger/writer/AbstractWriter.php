<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\core\service\logger\writer;

/**
 * Log writer abstract class
 */
abstract class AbstractWriter implements WriterInterface
{
    
    /**
     * Write to the log
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     *
     * @return AbstractWriter
     */
    abstract public function writeLog($level, $message, array $context = []);
    
    /**
     * Get context for log
     *
     * @param  array $context
     *
     * @return string
     */
    public function getContext(array $context = [])
    {
        $messageContext = '';
        
        if (isset($context['timestamp']))
        {
            unset($context['timestamp']);
        }
        if (isset($context['name']))
        {
            unset($context['name']);
        }
        if (isset($context['format']))
        {
            $format = $context['format'];
            unset($context['format']);
        }
        else
        {
            $format = 'text';
        }
        
        switch ($format)
        {
            case 'json':
                $messageContext = json_encode($context);
                break;
            default:
                foreach ($context as $key => $value)
                {
                    if (is_array($value))
                    {
                        $value = '[Array]';
                    }
                    if (is_object($value))
                    {
                        $value = '[Object]';
                    }
                    $messageContext .= (string)$key . '=' . (string)$value . ';';
                }
        }
        
        return $messageContext;
    }
    
}
