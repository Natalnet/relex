<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class SymbolNotDefinedException extends Exception
{
    public $codeLine;
    public $symbolName;

    public function __construct($line, $symbol, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->symbolName = $symbol;

        $message = "Line $line: Symbol $symbol not defined";

        parent::__construct($message, $code, $previous);
    }
}
