<?php

namespace Natalnet\Relex;

class Token
{
    /**
     * The type of the token.
     * @var int
     */
    public $type;

    /**
     * The text the token contains.
     * @var string
     */
    public $text;

    /**
     * The line the token is located.
     * @var int
     */
    public $line;

    /**
     * Create a new Token instance.
     *
     * @param $type int
     * @param $text string
     * @param $line int
     */
    public function __construct($type, $text, $line)
    {
        $this->type = $type;
        $this->text = $text;
        $this->line = $line;
    }

    /**
     * Renders the token as string.
     *
     * @return string
     */
    public function __toString()
    {
        $tname = ReducLexer::$tokenNames[$this->type];

        return "<'".$this->text."',".$tname.'>';
    }
}
