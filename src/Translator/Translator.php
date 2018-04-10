<?php

namespace Natalnet\Relex\Translator;

use Natalnet\Relex\Node\NodeInterface;
use Natalnet\Relex\ParseTree\ParseTreeInterface;
use Natalnet\Relex\ReducLexer;
use Natalnet\Relex\Token;
use Natalnet\Relex\Types;

/**
 * summary
 */
class Translator
{
    protected $parseTree;
    protected $translatedString = '';

    protected $header = '';
    protected $footer = '';

    protected $mainFunction = '';
    protected $taskDeclaration = '';
    protected $callFunction = "funcao(); ";


    protected $variableDeclarations = [
        Types::NUMBER_TYPE => '',
        Types::STRING_TYPE => '',
        Types::BOOLEAN_TYPE => ''
    ];


    protected $constTrue;
    protected $constFalse;


    protected $operators = [
        ReducLexer::T_E => '',
        ReducLexer::T_OU => '',
        ReducLexer::T_NEGATE => '',
        ReducLexer::T_EQUALS_EQUALS => '',
        ReducLexer::T_NOT_EQUAL => '',
        ReducLexer::T_GREATER_THAN => '',
        ReducLexer::T_GREATER_THAN_EQUAL => '',
        ReducLexer::T_LESS_THAN => '',
        ReducLexer::T_LESS_THAN_EQUAL => '',
    ];


    protected $ifStatement = '';
    protected $elseIfStatement = '';
    protected $elseStatement = '';
    protected $whileStatement = '';
    protected $repeatStatement = '';
    protected $switchStatement = '';
    protected $switchCaseStatement = '';
    protected $forStatement = '';
    protected $doStatement = '';

    protected $constBreak = '';

    protected $functions = [];

    /**
     * summary
     */
    public function __construct(ParseTreeInterface $parseTree)
    {
        $this->parseTree = $parseTree;
        $this->constTrue = 'true';
        $this->constFalse = 'false';
    }

    public function setMainFunction($mainFunction)
    {
        $this->mainFunction = $mainFunction;
    }

    public function setTaskDeclaration($taskDeclaration)
    {
        $this->taskDeclaration = $taskDeclaration;
    }

    public function setIfStatement($ifStatement)
    {
        $this->ifStatement = $ifStatement;
    }

    public function setElseIfStatement($elseIfStatement)
    {
        $this->elseIfStatement = $elseIfStatement;
    }

    public function setElseStatement($elseStatement)
    {
        $this->elseStatement = $elseStatement;
    }

    public function setWhileStatement($whileStatement)
    {
        $this->whileStatement = $whileStatement;
    }

    public function setRepeatStatement($repeatStatement)
    {
        $this->repeatStatement = $repeatStatement;
    }

    public function setSwitchStatement($switchStatement)
    {
        $this->switchStatement = $switchStatement;
    }

    public function setSwitchCaseStatement($switchCaseStatement)
    {
        $this->switchCaseStatement = $switchCaseStatement;
    }

    public function setForStatement($forStatement)
    {
        $this->forStatement = $forStatement;
    }

    public function setDoStatement($doStatement)
    {
        $this->doStatement = $doStatement;
    }

    public function setOperators(array $operators)
    {
        $this->operators[ReducLexer::T_E] = ' '.$operators[ReducLexer::T_E].' ' ?: ' ';
        $this->operators[ReducLexer::T_OU] = ' '.$operators[ReducLexer::T_OU].' ' ?: ' ';
        $this->operators[ReducLexer::T_NEGATE] = ' '.$operators[ReducLexer::T_NEGATE].' ' ?: ' ';
        $this->operators[ReducLexer::T_EQUALS_EQUALS] = ' '.$operators[ReducLexer::T_EQUALS_EQUALS].' ' ?: ' ';
        $this->operators[ReducLexer::T_NOT_EQUAL] = ' '.$operators[ReducLexer::T_NOT_EQUAL].' ' ?: ' ';
        $this->operators[ReducLexer::T_GREATER_THAN] = ' '.$operators[ReducLexer::T_GREATER_THAN].' ' ?: ' ';
        $this->operators[ReducLexer::T_GREATER_THAN_EQUAL] = ' '.$operators[ReducLexer::T_GREATER_THAN_EQUAL].' ' ?: ' ';
        $this->operators[ReducLexer::T_LESS_THAN] = ' '.$operators[ReducLexer::T_LESS_THAN].' ' ?: ' ';
        $this->operators[ReducLexer::T_LESS_THAN_EQUAL] = ' '.$operators[ReducLexer::T_LESS_THAN_EQUAL].' ' ?: ' ';
    }

    public function setVariableDeclarations(array $declarations)
    {
        $this->variableDeclarations[Types::NUMBER_TYPE] = $declarations[Types::NUMBER_TYPE] ?: '';
        $this->variableDeclarations[Types::STRING_TYPE] = $declarations[Types::STRING_TYPE] ?: '';
        $this->variableDeclarations[Types::BOOLEAN_TYPE] = $declarations[Types::BOOLEAN_TYPE] ?: '';
    }

    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    public function translate()
    {
        $node = $this->parseTree->getNode();
        foreach ($node->getChildren() as $child) {
            $this->translatedString .= $this->process($child);
        }
    }

    public function process(NodeInterface $node)
    {
        if (!($node->getValue() instanceof Token)) {
//            echo "\n\n".$node->getValue()."\n\n";
            switch ($node->getValue()) {
                case 'symbols':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;
                case 'defineNumber':
                    return $this->processVariableDefinition($node, Types::NUMBER_TYPE);
                case 'defineText':
                    return $this->processVariableDefinition($node, Types::STRING_TYPE);
                case 'defineBoolean':
                    return $this->processVariableDefinition($node, Types::BOOLEAN_TYPE);
                case 'declareTask':
                    $matches = [
                        'funcao' => $this->process($node->getChildren()[1]),
                        'comandos' => $this->process($node->getChildren()[3])
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->taskDeclaration);
                    break;
                case 'program':
                    return str_replace('comandos', $this->process($node->getChildren()[1]), $this->mainFunction);
                    break;
                case 'commands':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;
                case 'command':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;
                case 'useFunction':
                    return $this->process($node->getChildren()[0]);
                    break;
                case 'useVariable':
                    $matches = [
                        'variavel' => $this->process($node->getChildren()[0]),
                        'valor' => ""
                    ];
                    for ($i = 2; $i < sizeof($node->getChildren()); $i++) {
                        $matches['valor'] .= $this->process($node->getChildren()[$i]);
                    }
                    return str_replace(array_keys($matches), array_values($matches), "variavel = valor;");
                    break;
                case 'ifStatement':
                    $matches = [
                        'condicao' => $this->process($node->getChildren()[2]),
                        'comandos' => $this->process($node->getChildren()[6])
                    ];
                    $aditional = '';
                    if (isset($node->getChildren()[8])) {
                        for ($i = 8; $i < count($node->getChildren()); $i++) {
                            $aditional .= $this->process($node->getChildren()[$i]);
                        }
                    }
                    return str_replace(array_keys($matches), array_values($matches), $this->ifStatement) . $aditional;
                    break;

                case 'elseIfStatement':
//                    echo ">>>>>>>". $node->getChildren()[3]->getValue();
                    $matches = [
                        'condicao' => $this->process($node->getChildren()[3]),
                        'comandos' => $this->process($node->getChildren()[7])
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->elseIfStatement);
                    break;

                case 'elseStatement':
                    $matches = [
                        'comandos' => $this->process($node->getChildren()[2])
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->elseStatement);
                    break;

                case 'whileStatement':
                    $matches = [
                        'condicao' => $this->process($node->getChildren()[2]),
                        'comandos' => $this->process($node->getChildren()[6])
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->whileStatement);
                    break;

                case 'repeatStatement':
                    $matches = [
                        'var' => $this->process($node->getChildren()[1]),
                        'comandos' => $this->process($node->getChildren()[4])
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->repeatStatement);
                    break;

                case 'switchStatement':
                    $matches = [
                        'variavel' => $this->process($node->getChildren()[2]),
                        'casos' => $this->process($node->getChildren()[5]),
                        'comandos' => $this->process($node->getChildren()[8]),
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->switchStatement);
                    break;

                case 'switchCases':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;

                case 'switchCaseStatement':
                    $matches = [
                        'valor' => $this->process($node->getChildren()[1]),
                        'comandos' => $this->process($node->getChildren()[3]),
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->switchCaseStatement);
                    break;

                case 'forStatement':
                    $matches = [
                        'variavel' => $this->process($node->getChildren()[1]),
                        'valor1' => $this->process($node->getChildren()[3]),
                        'valor2' => $this->process($node->getChildren()[5]),
                        'passo' => $this->process($node->getChildren()[7]),
                        'comandos' => $this->process($node->getChildren()[10]),
                    ];
                    return str_replace(array_keys($matches), array_values($matches), $this->forStatement);
                    break;

                case 'condition':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;
                case 'identifier':
                    if (sizeof($node->getChildren()) == 1) {
                        return $this->process($node->getChildren()[0]);
                    } else {
                        if (isset($this->functions[$node->getChildren()[0]->getValue()->text])){
                            $function = $this->functions[$node->getChildren()[0]->getValue()->text];
                            for ($i = 2, $k = 1; $i < count($node->getChildren())-1; $i+=2, $k++) {
                                $function = preg_replace(
                                    '/var'.($k).'\(([a-zA-Z]+)\)/',
                                    $this->process($node->getChildren()[$i]),
                                    $function
                                );
                            }
                        } else {
                            $function = str_replace('funcao', $node->getChildren()[0]->getValue()->text, $this->callFunction);
                        }
                        return $function;
                    }
                    break;
                // default:
                //     // code...
                //     break;
            }
        } else {
            switch ($node->getValue()->type) {
                case ReducLexer::T_VERDADEIRO:
                    return $this->constTrue;
                    break;
                case ReducLexer::T_FALSO:
                    return $this->constFalse;
                    break;
                case ReducLexer::T_E:
                    return $this->operators[ReducLexer::T_E];
                    break;
                case ReducLexer::T_OU:
                    return $this->operators[ReducLexer::T_OU];
                    break;
                case ReducLexer::T_EQUALS_EQUALS:
                    return $this->operators[ReducLexer::T_EQUALS_EQUALS];
                    break;
                case ReducLexer::T_LESS_THAN:
                    return $this->operators[ReducLexer::T_LESS_THAN];
                    break;
                case ReducLexer::T_LESS_THAN_EQUAL:
                    return $this->operators[ReducLexer::T_LESS_THAN_EQUAL];
                    break;
                case ReducLexer::T_GREATER_THAN:
                    return $this->operators[ReducLexer::T_GREATER_THAN];
                case ReducLexer::T_GREATER_THAN_EQUAL:
                    return $this->operators[ReducLexer::T_GREATER_THAN_EQUAL];
                case ReducLexer::T_NUMBER:
                    return $node->getValue()->text;
                    break;
                case ReducLexer::T_IDENTIFIER:
                    return $node->getValue()->text;
                    break;
                case ReducLexer::T_OPEN_PARENTHESIS:
                case ReducLexer::T_CLOSE_PARENTHESIS:
                case ReducLexer::T_PLUS:
                case ReducLexer::T_MINUS:
                case ReducLexer::T_MULTIPLY:
                case ReducLexer::T_DIVIDE:
                case ReducLexer::T_EQUALS:
                case ReducLexer::T_STRING:
                    return ' '.$node->getValue()->text.' ';
                    break;
            }
        }
    }

    private function processVariableDefinition(NodeInterface $node, $type)
    {
        $matches = [
            'variavel' => $node->getChildren()[1]->getValue()->text,
            'valor' => $this->process($node->getChildren()[3])
        ];
        return str_replace(array_keys($matches), array_values($matches), $this->variableDeclarations[$type]);
    }

    private function processVariableUse(NodeInterface $node)
    {
        switch ($node->getValue()) {
            case Types::NUMBER_TYPE."TypeVariable":
                // var_dump($node->getChildren()[1]->getValue());
                echo "esse";
                break;
        }
        $matches = [
            'variavel' => $node->getChildren()[0]->getValue()->text,
            'valor' => $node->getChildren()[2]->getValue()->text
        ];
        return str_replace(array_keys($matches), array_values($matches), $this->variableDeclarations[Types::NUMBER_TYPE]);
    }

    public function getTranslation()
    {
        return $this->translatedString;
    }
}
