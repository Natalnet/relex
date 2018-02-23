<?php

include "vendor/autoload.php";

use Natalnet\Relex\FunctionSymbol;
use Natalnet\Relex\ReducLexer;
use Natalnet\Relex\ReducParser;
use Natalnet\Relex\Translator\Translator;
use Natalnet\Relex\Types;
use Natalnet\Relex\Visitor\PreOrderVisitor;

// $lexer = new ReducLexer("inicio se (a ! b) entao {} se (a == b) entao {} fim");
$lexer = new ReducLexer(file_get_contents("content.txt"));
$parser = new ReducParser($lexer);

$function = new FunctionSymbol('escrever', null, [Types::NUMBER_TYPE, Types::NUMBER_TYPE, Types::STRING_TYPE]);
$function2 = new FunctionSymbol('ultra', Types::NUMBER_TYPE, [Types::NUMBER_TYPE]);
$parser->symbolTable->define($function);
$parser->symbolTable->define($function2);

try {
    $parser->program();
    echo "Okay!\n";
    // $token = $lexer->nextToken();

    // while ($token->type != 1) {
    //     echo $token->text . "\n";
    //     $token = $lexer->nextToken();
    // }
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

// $trans = new Translator($parser->parseTree);
// $trans->setMainFunction("task main(){
//  comandos
// }
// ");
// $controlFlow = [
//     'ifStatement' => "if(condicao){
//  comandos1
// }else{
//  comandos2
// }
// ",
//     'repeatStatement' => 'for (int k = 0; k < var; k++) {
//           comandos
// }',
//     'whileStatement' => 'while(condicao){
//  comandos
// }'
// ];
// $trans->setControlFlowStatements($controlFlow);
// $trans->setOperators([
//     ReducLexer::T_EQUALS_EQUALS => '==',
//     ReducLexer::T_LESS_THAN => '<',
//     ReducLexer::T_GREATER_THAN => '>',
//     ReducLexer::T_LESS_THAN_EQUAL => '<=',
//     ReducLexer::T_GREATER_THAN_EQUAL => '>=',
//     ReducLexer::T_NOT_EQUAL => '!='
// ]);
// $trans->setFunctions([
//     'escrever' => "u8g.firstPage();
//   do
//   {
//  u8g.drawStr( var1(int), var2(int), var3(String));
//   } while( u8g.nextPage() );",
//     'ultra' => 'SensorUS(IN_var1(int))'
// ]);
// $trans->translate();
// echo $trans->getTranslation();
// $visitor = new PreOrderVisitor;
// $yield = $parser->parseTree->getNode()->accept($visitor);
// print_r($parser->parseTree);
