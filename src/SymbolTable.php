<?php

namespace Natalnet\Relex;

/**
* a
*/
class SymbolTable implements Scope
{
    protected $symbols;

    public function __construct()
    {
        # code...
    }

    public function getScopeName()
    {
        return "global";
    }

    public function getEnclosingScope()
    {
        return null;
    }

    public function define(Symbol $symbol)
    {
        $this->symbols[$symbol->getName()] = $symbol;
    }

    public function resolve($name)
    {
        return $this->symbols[$name];
    }

    public function isDefined($name)
    {
        return array_key_exists($name, $this->symbols);
    }
}
