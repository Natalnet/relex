<?php

namespace Natalnet\Relex;

use \Exception;

class ReducLexer extends Lexer
{
    // Non identifier tokens
    const T_NUMBER               = 2;
    const T_STRING               = 3;
    const T_COMMA                = 4;
    const T_DIVIDE               = 5;
    const T_DOT                  = 6;
    const T_EQUALS               = 7;
    const T_EQUALS_EQUALS        = 8;
    const T_GREATER_THAN         = 9;
    const T_GREATER_THAN_EQUAL   = 10;
    const T_LESS_THAN            = 11;
    const T_LESS_THAN_EQUAL      = 12;
    const T_MINUS                = 13;
    const T_MULTIPLY             = 14;
    const T_NEGATE               = 15;
    const T_NOT_EQUAL            = 16;
    const T_PLUS                 = 17;
    const T_SEMICOLON            = 18;
    const T_OPEN_PARENTHESIS     = 19;
    const T_CLOSE_PARENTHESIS    = 20;
    const T_OPEN_CURLY_BRACE     = 21;
    const T_CLOSE_CURLY_BRACE    = 22;

    // Identifier token
    const T_IDENTIFIER           = 100;

    // Keyword tokens
    const T_ATE                  = 200;
    const T_BOOLEANO             = 201;
    const T_DE                   = 202;
    const T_E                    = 203;
    const T_ENQUANTO             = 204;
    const T_ENTAO                = 205;
    const T_FALSO                = 206;
    const T_FAREI                = 207;
    const T_FIM                  = 208;
    const T_INICIO               = 209;
    const T_NUMERO               = 210;
    const T_OU                   = 211;
    const T_PARA                 = 212;
    const T_PASSO                = 213;
    const T_REPITA               = 214;
    const T_SAIR                 = 215;
    const T_SE                   = 216;
    const T_SENAO                = 217;
    const T_TAREFA               = 218;
    const T_TESTE                = 219;
    const T_TEXTO                = 220;
    const T_VERDADEIRO           = 221;
    const T_VEZES                = 222;

    public static $tokenNames = [
        "n/a",
        "<EOF>",
        "START",
        "END",
        "IDENTIFIER",
        "STRING"
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

    /**
     * Verifies if current character is a number.
     * @return boolean
     */
    public function isCurrentCharANumber()
    {
        return $this->char >= '0' &&
               $this->char <= '9';
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

                case '"':
                    return $this->handleString();
                    break;

                case '.':
                    $this->consume();
                    return new Token(self::T_DOT, '.');
                case ',':
                    $this->consume();
                    return new Token(self::T_COMMA, ',');
                case '=':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();
                        return new Token(self::T_EQUALS_EQUALS, '==');
                    }
                    return new Token(self::T_EQUALS, '=');
                case '>':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();
                        return new Token(self::T_GREATER_THAN_EQUAL, '>=');
                    }
                    return new Token(self::T_GREATER_THAN, '>');
                case '<':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();
                        return new Token(self::T_LESS_THAN_EQUALS, '<=');
                    }
                    return new Token(self::T_LOWER_THAN, '<');
                case '+':
                    $this->consume();
                    return new Token(self::T_PLUS, '+');
                case '-':
                    $this->consume();
                    return new Token(self::T_MINUS, '-');
                case '*':
                    $this->consume();
                    return new Token(self::T_MULTIPLY, '*');
                case '/':
                    $this->consume();
                    return new Token(self::T_DIVIDE, '/');
                case '!':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();
                        return new Token(self::T_NOT_EQUAL, '!=');
                    }
                    return new Token(self::T_NEGATE, '!');
                case ':':
                    $this->consume();
                    return new Token(self::T_SEMICOLON, ':');
                case '(':
                    $this->consume();
                    return new Token(self::T_OPEN_PARENTHESIS, '(');
                case ')':
                    $this->consume();
                    return new Token(self::T_CLOSE_PARENTHESIS, ')');
                case '{':
                    $this->consume();
                    return new Token(self::T_OPEN_CURLY_BRACE, '{');
                case '}':
                    $this->consume();
                    return new Token(self::T_CLOSE_CURLY_BRACE, '}');

                default:
                    if ($this->isCurrentCharALetter()) {
                        return $this->handleName();
                    } elseif ($this->isCurrentCharANumber()) {
                        return $this->handleNumber();
                    } else {
                        throw new Exception("Invalid character: ".$this->char);
                    }
            }
        }
        return new Token(self::EOF_TYPE, "<EOF>");
    }

    public function handleString()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
            if ($this->char == self::EOF) {
                throw new Exception("Expecting \" character, end of file reached.\n");
            }
        } while ($this->char != '"');

        $buffer .= $this->char;
        $this->consume();

        return new Token(self::T_STRING, $buffer);
    }

    public function handleName()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
        } while ($this->isCurrentCharALetter() || $this->isCurrentCharANumber());

        $name = 'self::T_' . strtoupper($buffer);
        if ($this->isKeyword($name)) {
            return new Token(constant($name), $buffer);
        }

        return new Token(self::T_IDENTIFIER, $buffer);
    }

    public function handleNumber()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
        } while ($this->isCurrentCharANumber());

        if ($this->char == '.') {
            $buffer .= $this->char;
            $this->consume();
        }

        while ($this->isCurrentCharANumber()) {
            $buffer .= $this->char;
            $this->consume();
        }

        return new Token(self::T_NUMBER, $buffer);
    }

    protected function isKeyword($name)
    {
        if (defined($name) && constant($name) > 200) {
            return true;
        }
        return false;
    }

    public function WS()
    {
        while (ctype_space($this->char)) {
            $this->consume();
        }
    }
}
