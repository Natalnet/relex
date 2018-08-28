<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class TypeMismatchException extends Exception
{
    public $codeLine;
    public $expectedType;
    public $foundType;

    public function __construct($line, $expectedType, $foundType, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->expectedType = $expectedType;
        $this->foundType = $foundType;

        $message = "Line $line: Expecting type $expectedType, found type $foundType";

        parent::__construct($message, $code, $previous);
    }
}
