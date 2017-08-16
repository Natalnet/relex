<?php

namespace Natalnet\Relex;

abstract class Lexer
{
    /**
     * Represent end of file char.
     */
    const EOF = -1;

    /**
     * Rrepresent EOF token type.
     */
    const EOF_TYPE = 1;

    /**
     * Input string.
     * @var string
     */
    protected $input;

    /**
     * Index into input of current character.
     * @var integer
     */
    protected $position = 0;

    /**
     * Current character.
     * @var string
     */
    protected $char;

    public function __construct($input)
    {
        $this->input = $input;
        $this->char = substr($input, $this->position, 1); // prime lookahead
    }

    public function consume()
    {
        $this->position++;

        if ($this->position >= strlen($this->input)) {
            $this->char = Lexer::EOF;
        } else {
            $this->char = substr($this->input, $this->position, 1);
        }
    }

    abstract public function nextToken();

    abstract public function getTokenName($tokenType);
}
