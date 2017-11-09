<?php

namespace Natalnet\Relex\Translator;

use Natalnet\Relex\Node\NodeInterface;
use Natalnet\Relex\ParseTree\ParseTreeInterface;
use Natalnet\Relex\ReducLexer;
use Natalnet\Relex\Token;

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

    public function setControlFlowStatements($statements)
    {
        $this->ifStatement = $statements['ifStatement'];
        $this->repeatStatement = $statements['repeatStatement'];
    }

    public function setOperators(array $operators)
    {
        $this->operators[ReducLexer::T_EQUALS_EQUALS] = $operators[ReducLexer::T_EQUALS_EQUALS];
    }

    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
    }

    public function translate()
    {
        $program = $this->parseTree->getNode();
        $this->translatedString = $this->process($program);
    }

    public function process(NodeInterface $node)
    {
        if (!($node->getValue() instanceof Token)) {
            switch ($node->getValue()) {
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
                    $temp = $this->functions[$node->getChildren()[0]->getValue()->text];
                    for ($i = 2, $k = 1; $i < count($node->getChildren())-1; $i+=2, $k++) {
                        $temp = preg_replace('/var'.($k).'\(([a-zA-Z]+)\)/', $node->getChildren()[$i]->getValue()->text, $temp);
                    }
                    return $temp;
                    break;
                case 'repeatStatement':
                    $matches = [
                        'var' => $this->process($node->getChildren()[1]),
                        'comandos' => $this->process($node->getChildren()[4])
                    ];
                    $times = $this->process($node->getChildren()[1]);
                    return str_replace(array_keys($matches), array_values($matches), $this->repeatStatement);
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
                case ReducLexer::T_NUMBER:
                    return $node->getValue()->text;
                    break;
            }
        }
    }

    public function getTranslation()
    {
        return $this->translatedString;
    }
}
