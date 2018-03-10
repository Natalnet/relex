<?php

namespace Natalnet\Relex;

use \Exception;
use phpDocumentor\Reflection\Types\This;

class ReducParser extends Parser
{
    public function program()
    {
        $this->parseTree->value('code');
        $this->symbols();
        $this->parseTree->tree('program');
        $this->match(ReducLexer::T_INICIO);
        $this->commands();
        $this->match(ReducLexer::T_FIM);
        $this->match(ReducLexer::EOF_TYPE);
        $this->parseTree->end();
    }

    private function symbols()
    {
        $this->parseTree->tree('symbols');
        do {
            $continue = false;

            switch ($this->fetchLookaheadType()) {
                case ReducLexer::T_NUMERO:
                    $continue = true;
                    $this->parseTree->tree('defineNumber');
                    $this->match(ReducLexer::T_NUMERO);
                    $this->varDefinition(Types::NUMBER_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_NUMBER);
                    $this->parseTree->end();
                    break;
                case ReducLexer::T_TEXTO:
                    $continue = true;
                    $this->parseTree->tree('defineText');
                    $this->match(ReducLexer::T_TEXTO);
                    $this->varDefinition(Types::STRING_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_STRING);
                    $this->parseTree->end();
                    break;
                case ReducLexer::T_BOOLEANO:
                    $continue = true;
                    $this->parseTree->tree('defineBoolean');
                    $this->match(ReducLexer::T_BOOLEANO);
                    $this->varDefinition(Types::BOOLEAN_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->matchBoolean();
                    $this->parseTree->end();
                    break;
                case ReducLexer::T_TAREFA:
                    $continue = true;
                    $this->taskDeclaration();
                    break;

                default:
                    # code...
                    break;
            }
        } while ($continue);
        $this->parseTree->end();
    }

    private function varDefinition($type)
    {
        $name = $this->fetchLookaheadToken()->text;
        $this->match(ReducLexer::T_IDENTIFIER);
        $variable = new VariableSymbol($name, $type);
        $this->symbolTable->define($variable);
    }

    private function taskDeclaration()
    {
        $name = $this->currentLookaheadToken()->text;
        $this->parseTree->tree('task');
        $this->match(ReducLexer::T_IDENTIFIER);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $task = new Symbol($name, null);
        $this->symbolTable->define($task);
        $this->parseTree->end();
    }

    private function matchBoolean()
    {
        if ($this->fetchLookaheadType() === ReducLexer::T_NEGATE) {
            $this->match(ReducLexer::T_NEGATE);
        }
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_VERDADEIRO:
                $this->match(ReducLexer::T_VERDADEIRO);
                break;
            case ReducLexer::T_FALSO:
                $this->match(ReducLexer::T_FALSO);
                break;
            case ReducLexer::T_IDENTIFIER:
                $this->matchSymbol(Types::BOOLEAN_TYPE);
                break;
            default:
                throw new Exception("Expecting boolean value, found ".$this->fetchLookaheadType());
        }
    }

    private function speculateBoolean()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchBoolean();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();
        return $success;
    }

    private function matchNumeric()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_NUMBER:
                $this->match(ReducLexer::T_NUMBER);
                break;
            case ReducLexer::T_IDENTIFIER:
                $this->matchSymbol(Types::NUMBER_TYPE);
                break;
            default:
                throw new Exception("Expecting numeric value, found ".$this->fetchLookaheadType());
        }
    }

    public function speculateNumericExpression()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchNumeric();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();
        return $success;
    }

    private function matchString()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_STRING:
                $this->match(ReducLexer::T_STRING);
                break;
            case ReducLexer::T_IDENTIFIER:
                $this->matchSymbol(Types::STRING_TYPE);
                break;
            default:
                throw new Exception("Expecting string value, found ".$this->currentLookaheadToken()->text);
        }
    }

    public function matchSymbol($type = null)
    {
        if ($this->symbolTable->isDefined($this->currentLookaheadToken()->text)) {
            $symbol = $this->symbolTable->resolve($this->currentLookaheadToken()->text);
            if ($type != null && $symbol->getType() != $type) {
                throw new Exception("Type mismatch.".$type);
            }
            switch (true) {
                case $this->isFunction($this->currentLookaheadToken()):
                    return $this->matchFunction();
                    break;
                case $this->isVariable($this->currentLookaheadToken()):
                    return $this->matchVariable();
                    break;
                default:
                    throw new Exception("Expecting symbol, found ".$this->fetchLookaheadType());
                    break;
            }
        } else {
            throw new Exception("Symbol not defined");
        }
    }

    protected function matchSymbolUse()
    {
        $symbol = $this->matchSymbol();
        if ($symbol instanceof VariableSymbol) {
            $this->match(ReducLexer::T_EQUALS);
            switch ($symbol->getType()) {
                case Types::BOOLEAN_TYPE:
                    $this->matchLogicalExpression();
                    break;
                case Types::NUMBER_TYPE:
                    $this->matchNumeric();
                    break;
                case Types::STRING_TYPE:
                    $this->matchString();
            }
        }
    }

    public function isFunction(Token $token)
    {
        if ($token->type == ReducLexer::T_IDENTIFIER) {
            if ($this->symbolTable->isDefined($token->text)) {
                $symbol = $this->symbolTable->resolve($token->text);
                return ($symbol instanceof FunctionSymbol);
            }
        }
        return false;
    }

    public function matchFunction()
    {
        $symbol = $this->symbolTable->resolve($this->currentLookaheadToken()->text);

        $this->parseTree->tree('identifier');
        $this->match(ReducLexer::T_IDENTIFIER);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        for ($i = 0; $i < $symbol->parameters; $i++) {
            if ($i > 0) {
                $this->match(ReducLexer::T_COMMA);
            }
            switch ($symbol->parameterTypes[$i]) {
                case Types::NUMBER_TYPE:
                    $this->matchNumeric();
                    break;
                case Types::STRING_TYPE:
                    $this->matchString();
                    break;
                case Types::BOOLEAN_TYPE:
                    $this->matchBoolean();
                    break;
                default:
                    throw new Exception('Unknown exception');
            }
        }
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->parseTree->end();
        return $symbol;
    }

    public function isVariable(Token $token)
    {
        if ($token->type == ReducLexer::T_IDENTIFIER) {
            if ($this->symbolTable->isDefined($token->text)) {
                $symbol = $this->symbolTable->resolve($token->text);
                return ($symbol instanceof VariableSymbol);
            }
        }
        return false;
    }

    public function matchVariable($type = null)
    {
        $symbol = $this->symbolTable->resolve($this->fetchLookaheadToken()->text);
        if ($type && $symbol->getType() != $type) {
            throw new Exception('Type mismatch.');
        }
        $this->parseTree->tree('identifier');
        $this->match(ReducLexer::T_IDENTIFIER);
        $this->parseTree->end();
        return $symbol;
    }

    public function commands()
    {
        $this->parseTree->tree('commands');
        do {
            $this->parseTree->tree('command');
            $continue = false;

            switch ($this->fetchLookaheadType()) {
                case ReducLexer::T_SE:
                    $continue = true;
                    $this->ifStatement();
                    break;
                case ReducLexer::T_ENQUANTO:
                    $continue = true;
                    $this->whileStatement();
                    break;
                case ReducLexer::T_FAREI:
                    $continue = true;
                    $this->doStatement();
                    break;
                case ReducLexer::T_REPITA:
                    $continue = true;
                    $this->repeatStatement();
                    break;
                case ReducLexer::T_IDENTIFIER:
                    $continue = true;
                    $this->matchSymbolUse();
                    break;

                default:
                    # code...
                    break;
            }
            $this->parseTree->end();
        } while ($continue);
        $this->parseTree->end();
    }

    public function ifStatement()
    {
        $this->parseTree->tree('ifStatement');
        $this->match(ReducLexer::T_SE);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_ENTAO);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        if ($this->fetchLookaheadType() == ReducLexer::T_SENAO) {
            $this->match(ReducLexer::T_SENAO);
            if ($this->fetchLookaheadType() == ReducLexer::T_SE) {
                $this->ifStatement();
            } else {
                $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
                $this->commands();
                $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
            }
        }
        $this->parseTree->end();
    }

    public function repeatStatement()
    {
        $this->parseTree->tree('repeatStatement');
        $this->match(ReducLexer::T_REPITA);
        $this->matchNumeric();
        $this->match(ReducLexer::T_VEZES);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->parseTree->end();
    }

    public function whileStatement()
    {
        $this->parseTree->tree('whileStatement');
        $this->match(ReducLexer::T_ENQUANTO);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_FAREI);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->parseTree->end();
    }

    public function doStatement()
    {
        $this->match(ReducLexer::T_FAREI);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->match(ReducLexer::T_ENQUANTO);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
    }

    private function matchMathOperation()
    {
        $this->parseTree->tree('math-operation');
        if ($this->fetchLookaheadType() === ReducLexer::T_OPEN_PARENTHESIS) {
            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
            $this->matchMathOperation();
            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        } else {
            $this->matchNumeric();
            while ($this->isMathOperator($this->currentLookaheadToken())) {
                $this->matchMathOperator();
                $this->matchMathOperation();
            }
        }
        $this->parseTree->end();
    }

    private function speculateMathOperation()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchMathOperation();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();
        return $success;
    }

    public function matchEqualityExpression()
    {
        if ($this->speculateRelationalExpression()){
            $this->matchRelationalExpression();
        } elseif ($this->speculateMathOperation()) {
            $this->matchMathOperation();
            $this->matchAny([ReducLexer::T_EQUALS_EQUALS, ReducLexer::T_NOT_EQUAL]);
            $this->matchMathOperation();
        } elseif ($this->speculateBoolean()) {
            $this->matchBoolean();
            $this->matchAny([ReducLexer::T_EQUALS_EQUALS, ReducLexer::T_NOT_EQUAL]);
            $this->matchBoolean();
        } else {
            $this->matchString();
            $this->matchAny([ReducLexer::T_EQUALS_EQUALS, ReducLexer::T_NOT_EQUAL]);
            $this->matchString();
        }
    }

    private function speculateEqualityExpression()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchEqualityExpression();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();
        return $success;
    }

    public function matchLogicalExpression()
    {
        if ($this->speculateEqualityExpression()) {
            $this->matchEqualityExpression();
        } else {
            $this->matchBoolean();
        }
        while (in_array($this->currentLookaheadToken()->type, [ReducLexer::T_E, ReducLexer::T_OU])) {
            $this->matchAny([ReducLexer::T_E, ReducLexer::T_OU]);
            if ($this->speculateEqualityExpression()) {
                $this->matchEqualityExpression();
            } else {
                $this->matchBoolean();
            }
        }
    }

    public function matchRelationalExpression()
    {
        if ($this->fetchLookaheadType() === ReducLexer::T_OPEN_PARENTHESIS) {
            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
            $this->matchRelationalExpression();
            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        } else {
            $this->matchMathOperation();
            $this->matchAny([
                    ReducLexer::T_GREATER_THAN,
                    ReducLexer::T_GREATER_THAN_EQUAL,
                    ReducLexer::T_LESS_THAN,
                    ReducLexer::T_LESS_THAN_EQUAL]
            );
            $this->matchMathOperation();
        }
    }

    private function speculateRelationalExpression()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchRelationalExpression();
        } catch (\Exception $e) {
            $success = false;
        }
        $this->release();
        return $success;
    }

    /**
     * @throws Exception
     */
    public function matchCondition()
    {
        $this->parseTree->tree('condition');
        $this->matchLogicalExpression();
        $this->parseTree->end();
    }

    public function matchComparisonOperator()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_EQUALS_EQUALS:
                $this->match(ReducLexer::T_EQUALS_EQUALS);
                break;
            case ReducLexer::T_GREATER_THAN:
                $this->match(ReducLexer::T_GREATER_THAN);
                break;
            case ReducLexer::T_GREATER_THAN_EQUAL:
                $this->match(ReducLexer::T_GREATER_THAN_EQUAL);
                break;
            case ReducLexer::T_LESS_THAN:
                $this->match(ReducLexer::T_LESS_THAN);
                break;
            case ReducLexer::T_LESS_THAN_EQUAL:
                $this->match(ReducLexer::T_LESS_THAN_EQUAL);
                break;
            case ReducLexer::T_NOT_EQUAL:
                $this->match(ReducLexer::T_NOT_EQUAL);
                break;

            default:
                throw new Exception("Expecting comparison operator, found ".$this->fetchLookaheadType());
        }
    }

    public function isLogicalOperator()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_E:
                return true;
            case ReducLexer::T_OU:
                return true;

            default:
                return false;
        }
    }

    public function matchLogicalOperator()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_E:
                $this->match(ReducLexer::T_E);
                break;
            case ReducLexer::T_OU:
                $this->match(ReducLexer::T_OU);
                break;

            default:
                throw new Exception("Expecting boolean operator, found ".$this->fetchLookaheadType());
        }
    }

    public function isMathOperator(Token $token)
    {
        switch ($token->type) {
            case ReducLexer::T_PLUS:
            case ReducLexer::T_MINUS:
            case ReducLexer::T_MULTIPLY:
            case ReducLexer::T_DIVIDE:
                return true;
            default:
                return false;
        }
    }

    public function matchMathOperator()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_PLUS:
                $this->match(ReducLexer::T_PLUS);
                break;
            case ReducLexer::T_MINUS:
                $this->match(ReducLexer::T_MINUS);
                break;
            case ReducLexer::T_MULTIPLY:
                $this->match(ReducLexer::T_MULTIPLY);
                break;
            case ReducLexer::T_DIVIDE:
                $this->match(ReducLexer::T_DIVIDE);
                break;
            default:
                throw new Exception("Expecting math operator, found ".$this->fetchLookaheadToken()->text);
        }
    }
}
