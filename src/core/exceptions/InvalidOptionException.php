<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace mako\http\exceptions;


use eiu\core\exceptions\CoreException;


/**
 * File Not Found Exception
 *
 * @package mako\http\exceptions
 */
class InvalidOptionException extends CoreException
{
    /**
     * file name
     *
     * @var null
     */
    private $file;
    
    /**
     * FileNotFoundException constructor.
     *
     * @param null $file
     * @param null $message
     */
    public function __construct($file = null, $message = null)
    {
        $this->file = $file ?: "Unknown File";
        
        if (!$message)
        {
            $message = "The file \"$file\" does not exist.";
        }
        
        parent::__construct($message, 0);
    }
    
    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file;
    }
}