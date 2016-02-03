<?php

// example call 
// view-source:http://test.dev.vk-web.de/php-parser/parse-example.php

error_reporting(E_ALL);
ini_set("display_errors", 1);

ini_set('xdebug.max_nesting_level', 3000);

define('DS', DIRECTORY_SEPARATOR);
require __DIR__.DS.'vendor'.DS.'autoload.php';

// require implode(DS, array(__DIR__, 'vendor', 'nikic', 'php-parser', 'lib', 'bootstrap.php') );

// example https://github.com/vitali-korezki/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown

require_once( implode(DS, array(__DIR__, 'Visualizer.php')) );

use PhpParser\Error;
use PhpParser\ParserFactory;

$code = file_get_contents( implode(DS, array(__DIR__, 'Draw.php')) );

$factory = new ParserFactory;
$parser = $factory->create(ParserFactory::ONLY_PHP5);

$serializer = new PhpParser\Serializer\XML;
$prettyPrinter = new PhpParser\PrettyPrinter\Standard();

$visualizer = new Visualizer;

try {
	$stmts = $parser->parse($code);
   
	// print_r($stmts);
   
	// echo $serializer->serialize($stmts);
	echo $visualizer->serialize($stmts);
	
	// echo '<pre>'.json_encode($stmts, JSON_PRETTY_PRINT).'</pre>';
	// echo '<pre>'.print_r($stmts, true).'</pre>';
	
	// just export the file
	// echo $prettyPrinter->prettyPrintFile($stmts);
} 
catch (Error $e) {
	echo 'Parse Error: ', $e->getMessage();
}