<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class SymbolRedeclaredException extends Exception
{
    public $codeLine;
    public $symbolName;

    public function __construct($line, $symbolName, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->symbolName = $symbolName;

        $message = "Line $line: Cannot redeclare symbol $symbolName.";

        parent::__construct($message, $code, $previous);
    }
}
