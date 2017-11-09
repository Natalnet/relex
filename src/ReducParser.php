<?php

namespace Natalnet\Relex;

use \Exception;

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

            switch ($this->lookahead->type) {
                case ReducLexer::T_NUMERO:
                    $continue = true;
                    $this->parseTree->tree('declareNumber');
                    $this->match(ReducLexer::T_NUMERO);
                    $this->varDeclaration(Types::NUMBER_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_NUMBER);
                    $this->parseTree->end();
                    break;
                case ReducLexer::T_TEXTO:
                    $continue = true;
                    $this->parseTree->tree('declareText');
                    $this->match(ReducLexer::T_TEXTO);
                    $this->varDeclaration(Types::STRING_TYPE);
                    $this->match(ReducLexer::T_EQUALS);
                    $this->match(ReducLexer::T_STRING);
                    $this->parseTree->end();
                    break;
                case ReducLexer::T_BOOLEANO:
                    $continue = true;
                    $this->parseTree->tree('declareBoolean');
                    $this->match(ReducLexer::T_BOOLEANO);
                    $this->varDeclaration(Types::BOOLEAN_TYPE);
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

    private function varDeclaration($type)
    {
        $name = $this->lookahead->text;
        $this->match(ReducLexer::T_IDENTIFIER);
        $variable = new VariableSymbol($name, $type);
        $this->symbolTable->define($variable);
    }

    private function taskDeclaration()
    {
        $name = $this->lookahead->text;
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
        switch ($this->lookahead->type) {
            case ReducLexer::T_VERDADEIRO:
                $this->match(ReducLexer::T_VERDADEIRO);
                break;
            case ReducLexer::T_FALSO:
                $this->match(ReducLexer::T_FALSO);
                break;
            default:
                throw new Exception("Expecting boolean value, found ".$this->lookahead->type);
        }
    }

    public function isNumber($token)
    {
        return $token->type == ReducLexer::T_NUMBER;
    }

    public function isBoolean($token)
    {
        return ($token->type == ReducLexer::T_VERDADEIRO || $token->type == ReducLexer::T_FALSO);
    }

    public function commands()
    {
        $this->parseTree->tree('commands');
        do {
            $continue = false;

            switch ($this->lookahead->type) {
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
                    $this->parseTree->tree('identifier');
                    $id1 = $this->fetchIdentifier($this->lookahead->text);
                    if ($id1 instanceof FunctionSymbol) {
                        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
                        for ($i = 0; $i < $id1->parameters; $i++) {
                            if ($i > 0) {
                                $this->match(ReducLexer::T_COMMA);
                            }
                            $this->match($id1->parameterTypes[$i]);
                        }
                        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
                    } elseif ($id1 instanceof VariableSymbol) {
                        $this->match(ReducLexer::T_EQUALS);
                        $id2 = $this->fetchIdentifier($this->lookahead->text);
                        if ($id2 instanceof FunctionSymbol) {
                            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
                            for ($i = 0; $i < $id2->parameters; $i++) {
                                if ($i > 0) {
                                    $this->match(ReducLexer::T_COMMA);
                                }
                                $this->match($id2->parameterTypes[$i]);
                            }
                            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
                        }
                    }
                    $this->parseTree->end();
                    break;

                default:
                    # code...
                    break;
            }
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
        if ($this->lookahead->type == ReducLexer::T_SENAO) {
            $this->match(ReducLexer::T_SENAO);
            if ($this->lookahead->type == ReducLexer::T_SE) {
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
        $this->match(ReducLexer::T_NUMBER);
        $this->match(ReducLexer::T_VEZES);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
        $this->parseTree->end();
    }

    public function whileStatement()
    {
        $this->match(ReducLexer::T_ENQUANTO);
        $this->match(ReducLexer::T_OPEN_PARENTHESIS);
        $this->matchCondition();
        $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
        $this->match(ReducLexer::T_FAREI);
        $this->match(ReducLexer::T_OPEN_CURLY_BRACE);
        $this->commands();
        $this->match(ReducLexer::T_CLOSE_CURLY_BRACE);
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

    public function matchCondition()
    {
        $this->parseTree->tree('condition');
        if ($this->lookahead->type == ReducLexer::T_OPEN_PARENTHESIS) {
            $this->match(ReducLexer::T_OPEN_PARENTHESIS);
            $this->matchCondition();
            $this->match(ReducLexer::T_CLOSE_PARENTHESIS);
            if ($this->isLogicalOperator()) {
                $this->matchLogicalOperator();
                $this->matchCondition();
            }
        } elseif ($this->lookahead->type == ReducLexer::T_IDENTIFIER) {
            $id1 = $this->fetchIdentifier($this->lookahead->text);
            $this->matchComparisonOperator();
            if ($this->lookahead->type == ReducLexer::T_IDENTIFIER) {
                $id2 = $this->fetchIdentifier($this->lookahead->text);
                if ($id1->getType() != $id2->getType()) {
                    throw new Exception("Type mismatch");
                }
            } elseif ($this->lookahead->type == ReducLexer::T_NUMBER) {
                if ($id1->getType() != Types::NUMBER_TYPE) {
                    throw new Exception("Type mismatch");
                }
                $this->match(ReducLexer::T_NUMBER);
            } elseif ($this->lookahead->type == ReducLexer::T_STRING) {
                if ($id1->getType() != Types::STRING_TYPE) {
                    throw new Exception("Type mismatch");
                }
                $this->match(ReducLexer::T_STRING);
            } elseif ($this->isBoolean($this->lookahead)) {
                if ($id1->getType() != Types::BOOLEAN_TYPE) {
                    throw new Exception("Type mismatch");
                }
                $this->matchBoolean();
            }
        } elseif ($this->isNumber($this->lookahead)) {
            $this->match(ReducLexer::T_NUMBER);
            $this->matchComparisonOperator();
            switch ($this->lookahead->type) {
                case ReducLexer::T_NUMBER:
                    $this->match(ReducLexer::T_NUMBER);
                    break;
                case ReducLexer::T_IDENTIFIER:
                    $id1 = $this->fetchIdentifier($this->lookahead->text);
                    if ($id1->getType() != Types::NUMBER_TYPE) {
                        throw new Exception("Type mismatch. Expecting an identifier of type number.");
                    }
                    break;

                default:
                    throw new Exception("Type mismatch. Expecting a number.");
            }
        } elseif ($this->isBoolean($this->lookahead)) {
            $this->matchBoolean();
        } elseif ($this->lookahead->type == ReducLexer::T_NEGATE) {
            $this->match(ReducLexer::T_NEGATE);
            $this->matchCondition();
        } else {
            throw new Exception("Unexpected '".$this->lookahead->text."'.");
        }
        $this->parseTree->end();
    }

    private function fetchIdentifier($name)
    {
        if ($this->symbolTable->isDefined($name)) {
            $this->match(ReducLexer::T_IDENTIFIER);
            return $this->symbolTable->resolve($name);
        }
        throw new Exception($this->lookahead->text." not defined");
    }

    public function matchComparisonOperator()
    {
        switch ($this->lookahead->type) {
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
                throw new Exception("Expecting comparison operator, found ".$this->lookahead->type);
        }
    }

    public function isLogicalOperator()
    {
        switch ($this->lookahead->type) {
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
        switch ($this->lookahead->type) {
            case ReducLexer::T_E:
                $this->match(ReducLexer::T_E);
                break;
            case ReducLexer::T_OU:
                $this->match(ReducLexer::T_OU);
                break;

            default:
                throw new Exception("Expecting boolean operator, found ".$this->lookahead->type);
        }
    }
}
