<?php

namespace Natalnet\Relex;

use Exception;
use Natalnet\Relex\Exceptions\TypeMismatchException;
use Natalnet\Relex\Exceptions\UnexpectedTokenException;
use Natalnet\Relex\ParseTree\ParseTree;

class Parser
{
    /**
     * The input lexer to be received.
     * @var Lexer
     */
    protected $input;

    /**
     * Dynamically-sized lookahead buffer.
     * @var array
     */
    protected $lookaheadBuffer;

    /**
     * Stack of index markers into lookahead buffer.
     * @var array
     */
    protected $markers = [];

    /**
     * Index of current lookahead token.
     * @var int
     */
    protected $position = 0;

    public $symbolTable;
    public $parseTree;

    /**
     * Creates a new Parser instance and consumes the first token.
     *
     * @param Lexer $lexer
     */
    public function __construct(Lexer $lexer)
    {
        $this->lookaheadBuffer = new LookaheadBuffer();
        $this->symbolTable = new SymbolTable();
        $this->parseTree = new ParseTree();
        $this->input = $lexer;
        $this->fillLookaheadBuffer(1);
    }

    /**
     * Match the token of specific type or throws exception.
     *
     * @param  int $type
     * @return void
     * @throws UnexpectedTokenException
     */
    public function match($type)
    {
        if ($this->fetchLookaheadType() == $type) {
            $this->getParseTree()->leaf($this->fetchLookaheadToken());
            $this->consume();
        } else {
            throw new UnexpectedTokenException($this->fetchLookaheadToken()->line, $this->input->getTokenName($type), $this->input->getTokenName($this->fetchLookaheadToken()));
        }
    }

    /**
     * Match the token if it is of any specific types or throws exception.
     *
     * @param array $types
     * @throws TypeMismatchException
     */
    public function matchAny(array $types)
    {
        if (in_array($this->fetchLookaheadType(), $types, true)) {
            $this->getParseTree()->leaf($this->fetchLookaheadToken());
            $this->consume();
        } else {
            throw new TypeMismatchException($this->fetchLookaheadToken()->line);
        }
    }

    /**
     * Fetch the token at the first index.
     *
     * @return Token
     */
    public function currentLookaheadToken()
    {
        return $this->fetchLookaheadToken(1);
    }

    /**
     * Fetch the token at a given index.
     * @param  int $i index of the token
     * @return Token
     */
    public function fetchLookaheadToken($i = 1)
    {
        $this->fillLookaheadBuffer($i);

        return $this->lookaheadBuffer->getTokenAt($this->position + $i - 1);
    }

    public function fetchLookaheadType($i = 1)
    {
        return $this->fetchLookaheadToken($i)->type;
    }

    /**
     * Make sure there are a particular number of tokens from current position.
     * @param  int $tokensNeeded index to fill lookahead buffer up to
     * @return void
     */
    private function fillLookaheadBuffer($tokensNeeded)
    {
        if ($this->position + $tokensNeeded > $this->lookaheadBuffer->size()) { // in case out of tokens
            $missingTokens = ($this->position + $tokensNeeded) - $this->lookaheadBuffer->size();
            $this->fillMissingTokens($missingTokens);
        }
    }

    /**
     * Fill any missing tokens in lookahead buffer.
     * @param  int $missingTokens number of tokens to fill
     * @return void
     */
    private function fillMissingTokens($missingTokens)
    {
        for ($i = 1; $i <= $missingTokens; $i++) {
            $this->lookaheadBuffer->loadToken($this->input->nextToken());
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
        if ($this->position == $this->lookaheadBuffer->size() && ! $this->isSpeculating()) {
            $this->position = 0;
            $this->lookaheadBuffer->clear();
        }
        $this->fillLookaheadBuffer(1);
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

    public function speculate($callback)
    {
        $success = true;
        $this->mark();
        try {
            $callback;
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    public function seek($marker)
    {
        $this->position = $marker;
    }

    private function isSpeculating()
    {
        return count($this->markers) > 0;
    }

    /**
     * @return ParseTree
     */
    protected function getParseTree()
    {
        if ($this->isSpeculating()) {
            return new ParseTree();
        }

        return $this->parseTree;
    }
}
