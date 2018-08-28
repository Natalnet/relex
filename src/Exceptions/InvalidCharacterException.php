<?php

namespace Natalnet\Relex\Exceptions;

use Exception;

class InvalidCharacterException extends Exception
{
    public $codeLine;
    public $character;

    /**
     * InvalidCharacterException constructor.
     * @param int $line
     * @param string $character
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($line, $character, $code = 0, Exception $previous = null)
    {
        $this->codeLine = $line;
        $this->character = $character;

        $message = "Line $line: Invalid Character '$character'";

        parent::__construct($message, $code, $previous);
    }
}
