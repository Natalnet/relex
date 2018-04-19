<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class SymbolNotDefinedException extends Exception
{
    public function __construct($line, $symbol, $code = 0, Exception $previous = null) {

        $message = "Line $line: Symbol $symbol not defined";

        parent::__construct($message, $code, $previous);
    }
}