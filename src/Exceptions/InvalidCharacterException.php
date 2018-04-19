<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class InvalidCharacterException extends Exception
{
    /**
     * InvalidCharacterException constructor.
     * @param int $line
     * @param string $character
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($line, $character, $code = 0, Exception $previous = null) {
        $message = "Line $line: Invalid Character '$character'";

        parent::__construct($message, $code, $previous);
    }
}