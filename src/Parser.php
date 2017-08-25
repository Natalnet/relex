<?php

namespace Natalnet\Relex;

use \Exception;

class Parser
{
    protected $input;
    protected $lookahead;
    protected $symbolTable;

    /**
     * Creates a new Parser instanse and consumes the first token.
     *
     * @param Lexer $lexer
     */
    public function __construct(Lexer $lexer)
    {
        $this->symbolTable = new SymbolTable();
        $this->input = $lexer;
        $this->consume();
    }

    /**
     * Match the token of specific type or throws exception.
     *
     * @param  int $type
     * @return void
     */
    public function match($type)
    {
        if ($this->lookahead->type == $type) {
            $this->consume();
        } else {
            throw new Exception("Expecting ".$type.", found ".$this->lookahead->type);
        }
    }

    /**
     * Consume the next token.
     *
     * @return void
     */
    public function consume()
    {
        $this->lookahead = $this->input->nextToken();
    }
}
