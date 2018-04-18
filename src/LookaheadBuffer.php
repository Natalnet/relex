<?php


namespace Natalnet\Relex;


class LookaheadBuffer
{

    protected $buffer = [];

    public function loadToken(Token $token)
    {
        $this->buffer[] = $token;
    }

    public function size()
    {
        return count($this->buffer);
    }

    public function clear()
    {
        $this->buffer = [];
    }

    public function getTokenAt($position)
    {
        return $this->buffer[$position];
    }

}
