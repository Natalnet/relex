<?php

namespace Natalnet\Relex;

use Natalnet\Relex\Exceptions\InvalidCharacterException;
use Natalnet\Relex\Exceptions\UnexpectedTokenException;

class ReducLexer extends Lexer
{
    // Non identifier tokens
    const T_NUMBER = 2;
    const T_STRING = 3;
    const T_COMMA = 4;
    const T_DIVIDE = 5;
    const T_DOT = 6;
    const T_EQUALS = 7;
    const T_EQUALS_EQUALS = 8;
    const T_GREATER_THAN = 9;
    const T_GREATER_THAN_EQUAL = 10;
    const T_LESS_THAN = 11;
    const T_LESS_THAN_EQUAL = 12;
    const T_MINUS = 13;
    const T_MULTIPLY = 14;
    const T_NEGATE = 15;
    const T_NOT_EQUAL = 16;
    const T_PLUS = 17;
    const T_COLON = 18;
    const T_OPEN_PARENTHESIS = 19;
    const T_CLOSE_PARENTHESIS = 20;
    const T_OPEN_CURLY_BRACE = 21;
    const T_CLOSE_CURLY_BRACE = 22;
    const T_SHARP = 23;

    // Identifier token
    const T_IDENTIFIER = 100;

    // Keyword tokens
    const T_ATE = 200;
    const T_BOOLEANO = 201;
    const T_CASO = 224;
    const T_DE = 202;
    const T_E = 203;
    const T_ENQUANTO = 204;
    const T_ENTAO = 205;
    const T_FALSO = 206;
    const T_FAREI = 207;
    const T_FIM = 208;
    const T_INICIO = 209;
    const T_NUMERO = 210;
    const T_OU = 211;
    const T_OUTROS = 212;
    const T_PARA = 213;
    const T_PASSO = 214;
    const T_REPITA = 215;
    const T_SAIR = 216;
    const T_SE = 217;
    const T_SENAO = 218;
    const T_TAREFA = 219;
    const T_TESTE = 220;
    const T_TEXTO = 221;
    const T_VERDADEIRO = 222;
    const T_VEZES = 223;

    public static $tokenNames = [
        self::EOF_TYPE               => '<fim do arquivo>',
        self::T_NUMBER               => 'numero',
        self::T_STRING               => 'texto',
        self::T_COMMA                => ',',
        self::T_DIVIDE               => '/',
        self::T_DOT                  => '.',
        self::T_EQUALS               => '=',
        self::T_EQUALS_EQUALS        => '==',
        self::T_GREATER_THAN         => '>',
        self::T_GREATER_THAN_EQUAL   => '>=',
        self::T_LESS_THAN            => '<',
        self::T_LESS_THAN_EQUAL      => '<=',
        self::T_MINUS                => '-',
        self::T_MULTIPLY             => '*',
        self::T_NEGATE               => '!',
        self::T_NOT_EQUAL            => '!=',
        self::T_PLUS                 => '+',
        self::T_COLON                => ':',
        self::T_OPEN_PARENTHESIS     => '(',
        self::T_CLOSE_PARENTHESIS    => ')',
        self::T_OPEN_CURLY_BRACE     => '{',
        self::T_CLOSE_CURLY_BRACE    => '}',
        self::T_SHARP                => '#',

        self::T_IDENTIFIER           => 'identificador',

        self::T_ATE                  => 'ate',
        self::T_BOOLEANO             => 'booleano',
        self::T_DE                   => 'de',
        self::T_E                    => 'e',
        self::T_ENQUANTO             => 'enquanto',
        self::T_ENTAO                => 'entao',
        self::T_FALSO                => 'falso',
        self::T_FAREI                => 'farei',
        self::T_FIM                  => 'fim',
        self::T_INICIO               => 'inicio',
        self::T_NUMERO               => 'numero',
        self::T_OU                   => 'ou',
        self::T_PARA                 => 'para',
        self::T_PASSO                => 'passo',
        self::T_REPITA               => 'repita',
        self::T_SAIR                 => 'sair',
        self::T_SE                   => 'se',
        self::T_SENAO                => 'senao',
        self::T_TAREFA               => 'tarefa',
        self::T_TESTE                => 'teste',
        self::T_TEXTO                => 'texto',
        self::T_VERDADEIRO           => 'verdadeiro',
        self::T_VEZES                => 'vezes',
    ];

    public function getTokenName($x)
    {
        return self::$tokenNames[$x];
    }

    /**
     * Verifies if current character is a letter.
     * @return bool
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
     * @return bool
     */
    public function isCurrentCharANumber()
    {
        return $this->char >= '0' &&
               $this->char <= '9';
    }

    /**
     * @return Token
     * @throws InvalidCharacterException
     * @throws UnexpectedTokenException
     */
    public function nextToken()
    {
        while ($this->char != self::EOF) {
            switch ($this->char) {
                case "\n":
                case "\r":
                    $this->newLine();
                    break;
                case ' ':
                case "\t":
                    $this->whiteSpace();
                    break;

                case '"':
                    return $this->handleString();
                    break;

                case '.':
                    $this->consume();

                    return new Token(self::T_DOT, '.', $this->line);
                case ',':
                    $this->consume();

                    return new Token(self::T_COMMA, ',', $this->line);
                case '=':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();

                        return new Token(self::T_EQUALS_EQUALS, '==', $this->line);
                    }

                    return new Token(self::T_EQUALS, '=', $this->line);
                case '>':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();

                        return new Token(self::T_GREATER_THAN_EQUAL, '>=', $this->line);
                    }

                    return new Token(self::T_GREATER_THAN, '>', $this->line);
                case '<':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();

                        return new Token(self::T_LESS_THAN_EQUAL, '<=', $this->line);
                    }

                    return new Token(self::T_LESS_THAN, '<', $this->line);
                case '+':
                    $this->consume();

                    return new Token(self::T_PLUS, '+', $this->line);
                case '-':
                    $this->consume();

                    return new Token(self::T_MINUS, '-', $this->line);
                case '*':
                    $this->consume();

                    return new Token(self::T_MULTIPLY, '*', $this->line);
                case '/':
                    $this->consume();

                    return new Token(self::T_DIVIDE, '/', $this->line);
                case '!':
                    $this->consume();
                    if ($this->char == '=') {
                        $this->consume();

                        return new Token(self::T_NOT_EQUAL, '!=', $this->line);
                    }

                    return new Token(self::T_NEGATE, '!', $this->line);
                case ':':
                    $this->consume();

                    return new Token(self::T_COLON, ':', $this->line);
                case '(':
                    $this->consume();

                    return new Token(self::T_OPEN_PARENTHESIS, '(', $this->line);
                case ')':
                    $this->consume();

                    return new Token(self::T_CLOSE_PARENTHESIS, ')', $this->line);
                case '{':
                    $this->consume();

                    return new Token(self::T_OPEN_CURLY_BRACE, '{', $this->line);
                case '}':
                    $this->consume();

                    return new Token(self::T_CLOSE_CURLY_BRACE, '}', $this->line);
                case '#':
                    $this->consume();
                    while (! $this->isNewLine($this->char)) {
                        $this->consume();
                    }
                    break;

                default:
                    if ($this->isCurrentCharALetter()) {
                        return $this->handleName();
                    } elseif ($this->isCurrentCharANumber()) {
                        return $this->handleNumber();
                    } else {
                        throw new InvalidCharacterException($this->line, $this->char);
                    }
            }
        }

        return new Token(self::EOF_TYPE, '<EOF>', $this->line);
    }

    /**
     * @return Token
     * @throws UnexpectedTokenException
     */
    public function handleString()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
            if ($this->char == self::EOF) {
                throw new UnexpectedTokenException($this->line, '"', $this->getTokenName(self::EOF_TYPE));
            }
        } while ($this->char != '"');

        $buffer .= $this->char;
        $this->consume();

        return new Token(self::T_STRING, $buffer, $this->line);
    }

    public function handleName()
    {
        $buffer = '';
        do {
            $buffer .= $this->char;
            $this->consume();
        } while ($this->isCurrentCharALetter() || $this->isCurrentCharANumber());

        $name = 'self::T_'.strtoupper($buffer);
        if ($this->isKeyword($name)) {
            return new Token(constant($name), $buffer, $this->line);
        }

        return new Token(self::T_IDENTIFIER, $buffer, $this->line);
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

        return new Token(self::T_NUMBER, $buffer, $this->line);
    }

    protected function isKeyword($name)
    {
        if (defined($name) && constant($name) >= 200) {
            return true;
        }

        return false;
    }

    private function isNewLine($char)
    {
        return $char === "\n" || $char === "\r";
    }

    public function newLine()
    {
        while ($this->isNewLine($this->char)) {
            $this->line++;
            $this->consume();
        }
    }

    public function whiteSpace()
    {
        while (ctype_space($this->char)) {
            $this->consume();
        }
    }
}
