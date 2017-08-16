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
     * Create a new Token instance.
     *
     * @param $type int
     * @param $text string
     */
    public function __construct($type, $text)
    {
        $this->type = $type;
        $this->text = $text;
    }

    /**
     * Renders the token as string
     *
     * @return string
     */
    public function __toString()
    {
        $tname = ReducLexer::$tokenNames[$this->type];
        return "<'" . $this->text . "'," . $tname . ">";
    }
}
