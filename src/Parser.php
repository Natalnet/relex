<?php

namespace Natalnet\Relex;

use Natalnet\Relex\ParseTree\ParseTree;
use \Exception;

class Parser
{
    protected $input;
    protected $lookahead;
    public $symbolTable;
    public $parseTree;

    /**
     * Creates a new Parser instanse and consumes the first token.
     *
     * @param Lexer $lexer
     */
    public function __construct(Lexer $lexer)
    {
        $this->symbolTable = new SymbolTable();
        $this->parseTree = new ParseTree();
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
            $this->parseTree->leaf($this->lookahead);
            $this->consume();
        } else {
            throw new Exception("Expecting ".$this->input->getTokenName($type).", found ".$this->lookahead->text);
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
