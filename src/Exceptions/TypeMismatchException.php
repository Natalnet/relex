<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class TypeMismatchException extends Exception
{
    public function __construct($line, $expectedType, $foundType, $code = 0, Exception $previous = null)
    {
        $message = "Line $line: Expecting type $expectedType, found type $foundType";

        parent::__construct($message, $code, $previous);
    }
}
