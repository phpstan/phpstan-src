<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPStan\File\FileReader;
use PHPStan\Node\Printer\Printer;
use PHPStan\Testing\PHPStanTestCase;

class TransformElseIfToFlatElseIfVisitorTest extends PHPStanTestCase
{

	public function test(): void
	{
		$parser = new SimpleParser(
			new Php7(new Emulative()),
			new NameResolver(),
		);
		$ast = $parser->parseFile(__DIR__ . '/data/transform-else-if-to-flat-else-if-before.php');

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor(new TransformElseIfToFlatElseIfVisitor());
		$ast = $nodeTraverser->traverse($ast);

		$this->assertSame(
			FileReader::read(__DIR__ . '/data/transform-else-if-to-flat-else-if-after.php'),
			"<?php\n" . (new Printer())->prettyPrint($ast) . "\n",
		);
	}

}
