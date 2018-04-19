<?php


use Natalnet\Relex\Exceptions\InvalidCharacterException;
use Natalnet\Relex\Exceptions\UnexpectedTokenException;
use Natalnet\Relex\ReducLexer;
use Natalnet\Relex\ReducParser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /** @test */
    public function it_fails_on_empty_program()
    {
        $this->expectException(InvalidCharacterException::class);

        $code = '';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }

    /** @test */
    public function it_fails_on_missing_inicio()
    {
        $this->expectException(UnexpectedTokenException::class);

        $code = 'fim';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }

    /** @test */
    public function it_fails_on_missing_fim()
    {
        $this->expectException(UnexpectedTokenException::class);

        $code = 'inicio';
        $lexer = new ReducLexer($code);
        $parser = new ReducParser($lexer);
        $parser->program();
    }
}
