<?php

namespace Natalnet\Relex\Exceptions;

use Exception;
use Natalnet\Relex\ReducLexer;


class UnexpectedTokenException extends Exception
{
    /**
     * UnexpectedTokenException constructor.
     * @param int $line
     * @param string $expectedToken
     * @param string $foundToken
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($line, $expectedToken, $foundToken, $code = 0, Exception $previous = null) {

        $message = "Line $line: Expecting '$expectedToken', found '$foundToken'";

        parent::__construct($message, $code, $previous);
    }
}