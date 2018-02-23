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
    protected $translatedString;

    protected $mainFunction;
    protected $ifStatement;
    protected $repeatStatement;
    protected $constTrue;
    protected $constFalse;
    protected $operators = [
        ReducLexer::T_EQUALS_EQUALS => ''
    ];
    protected $variablesDeclaration = [
        Types::NUMBER_TYPE => 'float variavel = valor; ',
        Types::STRING_TYPE => 'char variavel[] = valor; ',
        Types::BOOLEAN_TYPE => 'bool variavel = valor; '
    ];
    protected $functions = [];
    protected $callFunction = "funcao(); ";

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

    public function setControlFlowStatements($statements)
    {
        $this->ifStatement = $statements['ifStatement'];
        $this->repeatStatement = $statements['repeatStatement'];
        $this->whileStatement = $statements['whileStatement'];
    }

    public function setOperators(array $operators)
    {
        $this->operators = $operators;
    }

    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    public function translate()
    {
        $node = $this->parseTree->getNode();
        // var_dump($node);
        // $this->translatedString = $this->process($program);
        $this->translatedString = "";
        foreach ($node->getChildren() as $child) {
            $this->translatedString .= $this->process($child);
        }
    }

    public function process(NodeInterface $node)
    {
        // var_dump($node);
        if (!($node->getValue() instanceof Token)) {
            switch ($node->getValue()) {
                case 'symbols':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child); //$this->process($child);
                    }
                    return $temp;
                    break;
                case 'declareNumber':
                    return $this->processVariableDeclaration($node, Types::NUMBER_TYPE);
                case 'declareText':
                    return $this->processVariableDeclaration($node, Types::STRING_TYPE);
                case 'declareBoolean':
                    return $this->processVariableDeclaration($node, Types::BOOLEAN_TYPE);
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
                        // var_dump($temp);
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
                        'comandos1' => $this->process($node->getChildren()[6])
                    ];
                    if (isset($node->getChildren()[8])) {
                        $matches['comandos2'] = $this->process($node->getChildren()[10]);
                    } else {
                        $matches['comandos2'] = '';
                    }
                    return str_replace(array_keys($matches), array_values($matches), $this->ifStatement);
                    break;
                case 'condition':
                    $temp = "";
                    foreach ($node->getChildren() as $child) {
                        $temp .= $this->process($child);
                    }
                    return $temp;
                    break;
                case 'identifier':
                    // var_dump(sizeof($node->getChildren()));
                    if (sizeof($node->getChildren()) == 1) {
                        return $this->process($node->getChildren()[0]);
                    } else {
                        // var_dump($this->functions[$node->getChildren()[0]->getValue()->text]);
                        $function = $this->functions[$node->getChildren()[0]->getValue()->text];
                        for ($i = 2, $k = 1; $i < count($node->getChildren())-1; $i+=2, $k++) {
                            $function = preg_replace(
                                '/var'.($k).'\(([a-zA-Z]+)\)/',
                                $this->process($node->getChildren()[$i]),
                                $function
                            );
                        }
                        return $function;
                    }
                    break;
                case 'repeatStatement':
                    $matches = [
                        'var' => $this->process($node->getChildren()[1]),
                        'comandos' => $this->process($node->getChildren()[4])
                    ];
                    $times = $this->process($node->getChildren()[1]);
                    return str_replace(array_keys($matches), array_values($matches), $this->repeatStatement);
                    break;
                case 'whileStatement':
                    $matches = [
                        'condicao' => $this->process($node->getChildren()[2]),
                        'comandos' => $this->process($node->getChildren()[6])
                    ];
                    $times = $this->process($node->getChildren()[1]);
                    return str_replace(array_keys($matches), array_values($matches), $this->whileStatement);
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
                    return $node->getValue()->text;
                    break;
            }
        }
    }

    private function processVariableDeclaration(NodeInterface $node, $type)
    {
        $matches = [
            'variavel' => $node->getChildren()[1]->getValue()->text,
            'valor' => $this->process($node->getChildren()[3])
        ];
        return str_replace(array_keys($matches), array_values($matches), $this->variablesDeclaration[$type]);
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
        return str_replace(array_keys($matches), array_values($matches), $this->variablesDeclaration[Types::NUMBER_TYPE]);
    }

    public function getTranslation()
    {
        return $this->translatedString;
    }
}
