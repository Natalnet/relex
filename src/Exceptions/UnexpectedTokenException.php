<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class UnexpectedTokenException extends Exception
{
    public $codeLine;
    public $expectedToken;
    public $foundToken;

    /**
     * UnexpectedTokenException constructor.
     * @param int $line
     * @param string $expectedToken
     * @param string $foundToken
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($line, $expectedToken, $foundToken, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->expectedToken = $expectedToken;
        $this->foundToken = $foundToken;

        $message = "Line $line: Expecting '$expectedToken', found '$foundToken'";

        parent::__construct($message, $code, $previous);
    }
}
