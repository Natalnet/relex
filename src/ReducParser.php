<?php

namespace Natalnet\Relex;

use Exception;
use Natalnet\Relex\Exceptions\TypeMismatchException;
use Natalnet\Relex\Exceptions\SymbolNotDefinedException;
use Natalnet\Relex\Exceptions\UnexpectedTokenException;

class ReducParser extends Parser
{
    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function parse()
    {
        $this->getParseTree()->value('code');
        $this->symbols();
        $this->getParseTree()->tree('program');
        $this->match(ReducLexer::T_INICIO);
        $this->commands();
        $this->match(ReducLexer::T_FIM);
        $this->match(ReducLexer::EOF_TYPE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    private function symbols()
    {
        $this->getParseTree()->tree('symbols');
        do {
            $continue = false;

            switch ($this->fetchLookaheadType()) {
                case ReducLexer::T_NUMERO:
                    $continue = true;
                    $this->getParseTree()->tree('defineNumber');
                    $this->match(ReducLexer::T_NUMERO);
                    $this->varDefinition(Types::NUMBER_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_NUMBER);
                    $this->getParseTree()->end();
                    break;
                case ReducLexer::T_TEXTO:
                    $continue = true;
                    $this->getParseTree()->tree('defineText');
                    $this->match(ReducLexer::T_TEXTO);
                    $this->varDefinition(Types::STRING_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_STRING);
                    $this->getParseTree()->end();
                    break;
                case ReducLexer::T_BOOLEANO:
                    $continue = true;
                    $this->getParseTree()->tree('defineBoolean');
                    $this->match(ReducLexer::T_BOOLEANO);
                    $this->varDefinition(Types::BOOLEAN_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->matchBoolean();
                    $this->getParseTree()->end();
                    break;
                case ReducLexer::T_TAREFA:
                    $continue = true;
                    $this->taskDeclaration();
                    break;

                default:
                    // code...
                    break;
            }
        } while ($continue);
        $this->getParseTree()->end();
    }

    /**
     * @param $type
     * @throws UnexpectedTokenException
     */
    private function varDefinition($type)
    {
        $name = $this->fetchLookaheadToken()->text;
        $this->match(ReducLexer::T_IDENTIFIER);
        $variable = new VariableSymbol($name, $type);
        $this->symbolTable->define($variable);
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    private function taskDeclaration()
    {
        $this->getParseTree()->tree('declareTask');
        $this->match(ReducLexer::T_TAREFA);

        $name = $this->currentLookaheadToken()->text;

        $this->match(ReducLexer::T_IDENTIFIER);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $task = new FunctionSymbol($name, null, []);
        $this->symbolTable->define($task);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
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
                throw new TypeMismatchException($this->currentLookaheadToken()->line, Types::getTypeName(Types::BOOLEAN_TYPE), Types::getTypeName($this->fetchLookaheadType()));
        }
    }

    /**
     * @return bool
     */
    private function speculateBoolean()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchBoolean();
        } catch (Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
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
                throw new TypeMismatchException($this->fetchLookaheadToken()->line, Types::NUMBER_TYPE, $this->fetchLookaheadType());
        }
    }

    public function speculateNumericExpression()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchNumeric();
        } catch (Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
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
                throw new UnexpectedTokenException($this->fetchLookaheadToken()->line, null, $this->currentLookaheadToken()->text);
        }
    }

    /**
     * @param int|null $type
     * @return FunctionSymbol|VariableSymbol
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    protected function matchSymbol($type = null)
    {
        if ($this->symbolTable->isDefined($this->currentLookaheadToken()->text)) {
            $symbol = $this->symbolTable->resolve($this->currentLookaheadToken()->text);
            if ($type != null && $symbol->getType() != $type) {
                throw new TypeMismatchException($this->currentLookaheadToken()->line, Types::getTypeName($type), Types::getTypeName($symbol->getType()));
            }
            if ($this->isFunction($this->currentLookaheadToken())) {
                return $this->matchFunction();
            } else {
                return $this->matchVariable();
            }
        } else {
            throw new SymbolNotDefinedException($this->currentLookaheadToken()->line, $this->currentLookaheadToken()->text);
        }
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    protected function matchSymbolUse()
    {
        $this->getParseTree()->tree('symbolUse');
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
        $this->getParseTree()->end();
    }

    public function isFunction(Token $token)
    {
        if ($token->type == ReducLexer::T_IDENTIFIER) {
            if ($this->symbolTable->isDefined($token->text)) {
                $symbol = $this->symbolTable->resolve($token->text);

                return $symbol instanceof FunctionSymbol;
            }
        }

        return false;
    }

    /**
     * @return mixed
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function matchFunction()
    {
        $symbol = $this->symbolTable->resolve($this->currentLookaheadToken()->text);

        $this->getParseTree()->tree('identifier');
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
            }
        }
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->getParseTree()->end();

        return $symbol;
    }

    public function isVariable(Token $token)
    {
        if ($token->type == ReducLexer::T_IDENTIFIER) {
            if ($this->symbolTable->isDefined($token->text)) {
                $symbol = $this->symbolTable->resolve($token->text);

                return $symbol instanceof VariableSymbol;
            }
        }

        return false;
    }

    /**
     * @param null $type
     * @return mixed
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function matchVariable($type = null)
    {
        if ($this->symbolTable->isDefined($this->currentLookaheadToken()->text)) {
            $symbol = $this->symbolTable->resolve($this->fetchLookaheadToken()->text);
            if ($type && $symbol->getType() != $type) {
                throw new TypeMismatchException($this->currentLookaheadToken()->line, $type, $symbol->getType());
            }
            $this->getParseTree()->tree('identifier');
            $this->match(ReducLexer::T_IDENTIFIER);
            $this->getParseTree()->end();

            return $symbol;
        } else {
            throw new SymbolNotDefinedException($this->currentLookaheadToken()->line, $this->currentLookaheadToken()->text);
        }
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function commands()
    {
        $this->getParseTree()->tree('commands');
        do {
            $this->getParseTree()->tree('command');
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
                case ReducLexer::T_REPITA:
                    $continue = true;
                    $this->repeatStatement();
                    break;
                case ReducLexer::T_TESTE:
                    $continue = true;
                    $this->switchStatement();
                    break;
                case ReducLexer::T_PARA:
                    $continue = true;
                    $this->forStatement();
                    break;
                case ReducLexer::T_FAREI:
                    $continue = true;
                    $this->doStatement();
                    break;
                case ReducLexer::T_IDENTIFIER:
                    $continue = true;
                    $this->matchSymbolUse();
                    break;

                default:
                    // code...
                    break;
            }
            $this->getParseTree()->end();
        } while ($continue);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function ifStatement()
    {
        $this->getParseTree()->tree('ifStatement');
        $this->match(ReducLexer::T_SE);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_ENTAO);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        while ($this->fetchLookaheadType() == ReducLexer::T_SENAO && $this->fetchLookaheadType(2) == ReducLexer::T_SE) {
            $this->elseIfStatement();
        }
        if ($this->fetchLookaheadType() == ReducLexer::T_SENAO) {
            $this->elseStatement();
        }
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function elseIfStatement()
    {
        $this->getParseTree()->tree('elseIfStatement');
        $this->match(ReducLexer::T_SENAO);
        $this->match(ReducLexer::T_SE);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_ENTAO);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function elseStatement()
    {
        $this->getParseTree()->tree('elseStatement');
        $this->match(ReducLexer::T_SENAO);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function whileStatement()
    {
        $this->getParseTree()->tree('whileStatement');
        $this->match(ReducLexer::T_ENQUANTO);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_FAREI);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function repeatStatement()
    {
        $this->getParseTree()->tree('repeatStatement');
        $this->match(ReducLexer::T_REPITA);
        $this->matchNumeric();
        $this->match(ReducLexer::T_VEZES);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function switchStatement()
    {
        $this->getParseTree()->tree('switchStatement');
        $this->match(ReducLexer::T_TESTE);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $variable = $this->matchVariable();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->getParseTree()->tree('switchCases');
        while ($this->fetchLookaheadType() === ReducLexer::T_CASO) {
            $this->getParseTree()->tree('switchCaseStatement');
            $this->match(ReducLexer::T_CASO);
            switch ($variable->getType()) {
                case Types::NUMBER_TYPE:
                    $this->matchNumeric();
                    break;
                case Types::BOOLEAN_TYPE:
                    $this->matchBoolean();
                    break;
                case Types::STRING_TYPE:
                    $this->matchString();
                    break;
            }
            $this->match(ReducLexer::T_SEMICOLON);
            $this->commands();
            $this->getParseTree()->end();
        }
        $this->getParseTree()->end();
        $this->match(ReducLexer::T_OUTROS);
        $this->match(ReducLexer::T_SEMICOLON);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function forStatement()
    {
        $this->getParseTree()->tree('forStatement');
        $this->match(ReducLexer::T_PARA);
        $this->matchVariable(Types::NUMBER_TYPE);
        $this->match(ReducLexer::T_DE);
        $this->matchNumeric();
        $this->match(ReducLexer::T_ATE);
        $this->matchNumeric();
        $this->match(ReducLexer::T_PASSO);
        $this->matchNumeric();
        $this->match(ReducLexer::T_FAREI);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->getParseTree()->end();
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
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

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    private function matchMathOperation()
    {
//        $this->getParseTree()->tree('math-operation');
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
//        $this->getParseTree()->end();
    }

    private function speculateMathOperation()
    {
        $success = true;
        $this->mark();
        try {
            $this->matchMathOperation();
        } catch (Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function matchEqualityExpression()
    {
        if ($this->speculateRelationalExpression()) {
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
        } catch (Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function matchLogicalExpression()
    {
        if ($this->fetchLookaheadType() === ReducLexer::T_OPEN_PARENTHESIS) {
            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
            $this->matchLogicalExpression();
            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        } else {
            if ($this->speculateEqualityExpression()) {
                $this->matchEqualityExpression();
            } else {
                $this->matchBoolean();
            }
        }
        while (in_array($this->currentLookaheadToken()->type, [ReducLexer::T_E, ReducLexer::T_OU])) {
            $this->matchAny([ReducLexer::T_E, ReducLexer::T_OU]);
            if ($this->speculateEqualityExpression()) {
                $this->matchEqualityExpression();
            } elseif ($this->fetchLookaheadType() === ReducLexer::T_OPEN_PARENTHESIS) {
                $this->matchLogicalExpression();
            } else {
                $this->matchBoolean();
            }
        }

//        $parenthesis = false;
//        $innerParenthesis = false;
//        if ($this->currentLookaheadToken() === ReducLexer::T_OPEN_PARENTHESIS){
//            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
//        }
//        if ($this->speculateEqualityExpression()) {
//            $this->matchEqualityExpression();
//        } else {
//            $this->matchBoolean();
//        }
//        while (in_array($this->currentLookaheadToken()->type, [ReducLexer::T_E, ReducLexer::T_OU])) {
//            $this->matchAny([ReducLexer::T_E, ReducLexer::T_OU]);
//            if ($this->speculateEqualityExpression()) {
//                $this->matchEqualityExpression();
//            } else {
//                $this->matchBoolean();
//            }
//        }
//        if ($parenthesis) {
//            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
//        }
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
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
                    ReducLexer::T_LESS_THAN_EQUAL, ]
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
        } catch (Exception $e) {
            $success = false;
        }
        $this->release();

        return $success;
    }

    /**
     * @throws SymbolNotDefinedException
     * @throws TypeMismatchException
     * @throws UnexpectedTokenException
     */
    public function matchCondition()
    {
        $this->getParseTree()->tree('condition');
        $this->matchLogicalExpression();
        $this->getParseTree()->end();
    }

    /**
     * @throws UnexpectedTokenException
     */
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
                throw new UnexpectedTokenException($this->fetchLookaheadToken()->line, null, $this->fetchLookaheadToken());
        }
    }

    public function isLogicalOperator()
    {
        switch ($this->fetchLookaheadType()) {
            case ReducLexer::T_OU:
            case ReducLexer::T_E:
                return true;

            default:
                return false;
        }
    }

    /**
     * @throws UnexpectedTokenException
     */
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
                throw new UnexpectedTokenException($this->fetchLookaheadToken()->line, null, $this->fetchLookaheadToken());
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

    /**
     * @throws UnexpectedTokenException
     */
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
                throw new UnexpectedTokenException($this->fetchLookaheadToken()->line, null, $this->fetchLookaheadToken());
        }
    }
}
