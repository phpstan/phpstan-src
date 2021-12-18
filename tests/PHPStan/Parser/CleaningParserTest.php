<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\File\FileReader;
use PHPStan\Testing\PHPStanTestCase;

class CleaningParserTest extends PHPStanTestCase
{

	public function dataParse(): iterable
	{
		return [
			[
				__DIR__ . '/data/cleaning-1-before.php',
				__DIR__ . '/data/cleaning-1-after.php',
			],
		];
	}

	/**
	 * @dataProvider dataParse
	 */
	public function testParse(
		string $beforeFile,
		string $afterFile,
	): void
	{
		$parser = new CleaningParser(
			new SimpleParser(
				new Php7(new Emulative()),
				new NameResolver(),
			),
		);
		$printer = new Standard();
		$ast = $parser->parseFile($beforeFile);
		$this->assertSame(FileReader::read($afterFile), "<?php\n" . $printer->prettyPrint($ast) . "\n");
	}

}
