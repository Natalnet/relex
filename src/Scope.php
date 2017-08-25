<?php

namespace Natalnet\Relex;

interface Scope
{
    public function getScopeName();

    public function getEnclosingScope();

    public function define(Symbol $symbol);

    public function resolve($name);
}
