<?php


use Natalnet\Relex\ReducLexer;
use Natalnet\Relex\ReducParser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /** @test */
    public function it_fails_on_empty_program()
    {
        $this->expectExceptionMessage('Invalid character: ');

        $code = '';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }

    /** @test */
    public function it_fails_on_missing_inicio()
    {
        $this->expectExceptionMessage('Expecting inicio, found');

        $code = 'fim';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }

    /** @test */
    public function it_fails_on_missing_fim()
    {
        $this->expectExceptionMessage('Expecting fim, found <EOF>');

        $code = 'inicio';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }
}
