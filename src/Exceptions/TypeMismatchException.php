<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class TypeMismatchException extends Exception
{
    public $codeLine;
    public $expectedType;
    public $foundType;

    /**
     * TypeMismatchException constructor.
     *
     * @param int $line
     * @param string|null $expectedType
     * @param string|null $foundType
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($line, $expectedType = null, $foundType = null, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->expectedType = $expectedType;
        $this->foundType = $foundType;

        $message = "Line $line: Expecting type $expectedType, found type $foundType";

        parent::__construct($message, $code, $previous);
    }
}
