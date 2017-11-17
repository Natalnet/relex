<?php

namespace Natalnet\Relex;

use Natalnet\Relex\ParseTree\ParseTree;
use \Exception;

class Parser
{
    /**
     * The input lexer to be received
     * @var Lexer
     */
    protected $input;

    /**
     * Ddynamically-sized lookahead buffer
     * @var array
     */
    protected $lookahead = [];

    /**
     * Stack of index markers into lookahead buffer
     * @var array
     */
    protected $markers = [];

    /**
     * Index of current lookahead token
     * @var integer
     */
    protected $position = 0;

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
        $this->sync(1);
    }

    /**
     * Match the token of specific type or throws exception.
     *
     * @param  int $type
     * @return void
     */
    public function match($type)
    {
        if ($this->fetchLookaheadType() == $type) {
            $this->parseTree->leaf($this->fetchLookaheadToken());
            $this->consume();
        } else {
            throw new Exception("Expecting ".$this->input->getTokenName($type).", found ".$this->fetchLookaheadToken()->text);
        }
    }

    /**
     * Fetch the token at a given index
     * @param  integer $i index of the token
     * @return Token
     */
    public function fetchLookaheadToken($i = 1)
    {
        $this->sync($i);
        return $this->lookahead[$this->position + $i - 1];
    }

    public function fetchLookaheadType($i = 1)
    {
        return $this->fetchLookaheadToken($i)->type;
    }

    /**
     * Make sure there are i tokens from current position
     * @param  integer $i index to sync
     * @return void
     */
    private function sync($i)
    {
        if ($this->position + $i - 1 > (count($this->lookahead) - 1)) { // in case out of tokens
            $missingTokens = ($this->position + $i - 1) - (count($this->lookahead) - 1);
            $this->fill($missingTokens);
        }
    }

    /**
     * Fill any missing tokens in lookahead buffer
     * @param  integer $missingTokens number of tokens to fill
     * @return void
     */
    private function fill($missingTokens)
    {
        for ($i = 1; $i <= $missingTokens; $i++) {
            $this->lookahead[] = $this->input->nextToken();
        }
    }

    /**
     * Consume the next token.
     *
     * @return void
     */
    public function consume()
    {
        $this->position++;
        if ($this->position == count($this->lookahead) && !$this->isSpeculating()) {
            $this->position = 0;
            $this->lookahead = [];
        }
        $this->sync(1);
    }

    public function mark()
    {
        $this->markers[] = $this->position;
        return $this->position;
    }

    public function release()
    {
        $marker = array_pop($this->markers);
        $this->seek($marker);
    }

    public function seek($marker)
    {
        $this->position = $marker;
    }

    private function isSpeculating()
    {
        return count($this->markers > 0);
    }
}
