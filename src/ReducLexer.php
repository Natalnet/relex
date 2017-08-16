<?php

namespace Natalnet\Relex;

use \Exception;

class ReducLexer extends Lexer
{
    const START = 2;
    const END = 3;
    const IDENTIFIER = 4;

    public static $tokenNames = [
        "n/a",
        "<EOF>",
        "START",
        "END",
        "IDENTIFIER"
    ];

    public function getTokenName($x)
    {
        return ListLexer::$tokenNames[$x];
    }

    /**
     * Verifies if current character is a letter.
     * @return boolean
     */
    public function isCurrentCharALetter()
    {
        return $this->char >= 'a' &&
               $this->char <= 'z' ||
               $this->char >= 'A' &&
               $this->char <= 'Z';
    }

    public function nextToken()
    {
        while ($this->char != self::EOF) {
            switch ($this->char) {
                case ' ':
                case "\t":
                case "\n":
                case "\r":
                    $this->WS();
                    break;

                default:
                    if ($this->isCurrentCharALetter()) {
                        return $this->handleName();
                    } else {
                        throw new Exception("Invalid character: ".$this->char);
                    }
            }
        }
        return new Token(self::EOF_TYPE, "<EOF>");
    }

    public function handleName()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
        } while ($this->isCurrentCharALetter());

        switch ($buffer) {
            case 'inicio':
                return new Token(self::START, $buffer);
            case 'fim':
                return new Token(self::END, $buffer);

            default:
                return new Token(self::IDENTIFIER, $buffer);
        }
    }

    public function WS()
    {
        while (ctype_space($this->char)) {
            $this->consume();
        }
    }
}
