<?php

namespace Natalnet\Relex;

/**
 * summary.
 */
class FunctionSymbol extends Symbol
{
    public $parameters = 0;
    public $parameterTypes = [];

    public function __construct($name, $type, $parameterTypes)
    {
        parent::__construct($name, $type);
        if (is_array($parameterTypes)) {
            $this->parameters = count($parameterTypes);
            $this->parameterTypes = $parameterTypes;
        } else {
            $this->parameters = 1;
            array_push($this->parameterTypes, $parameterTypes);
        }
    }
}
