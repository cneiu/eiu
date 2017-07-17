<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2017 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */


namespace eiu\core\service\debug;


use ErrorException;
use ParseError;
use Throwable;
use TypeError;


/**
 * Defines a wrapper for fatal throwable errors
 */
class FatalThrowableError extends ErrorException
{
    public function __construct(Throwable $error)
    {
        if ($error instanceof TypeError)
        {
            $message  = "Type error: {$error->getMessage()}";
            $severity = E_RECOVERABLE_ERROR;
        }
        else if ($error instanceof ParseError)
        {
            $message  = "Parse error: {$error->getMessage()}";
            $severity = E_PARSE;
        }
        else
        {
            $message  = "Fatal error: {$error->getMessage()}";
            $severity = E_ERROR;
        }
        
        parent::__construct($message, $error->getCode(), $severity, $error->getFile(), $error->getLine());
    }
}
