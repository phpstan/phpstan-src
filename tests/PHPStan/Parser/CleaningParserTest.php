<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php8;
use PHPStan\File\FileReader;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use const PHP_VERSION_ID;

class CleaningParserTest extends PHPStanTestCase
{

	public function dataParse(): iterable
	{
		return [
			[
				__DIR__ . '/data/cleaning-1-before.php',
				__DIR__ . '/data/cleaning-1-after.php',
				PHP_VERSION_ID,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before.php',
				__DIR__ . '/data/cleaning-php-version-after-81.php',
				80100,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before.php',
				__DIR__ . '/data/cleaning-php-version-after-81.php',
				80200,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before.php',
				__DIR__ . '/data/cleaning-php-version-after-74.php',
				70400,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before2.php',
				__DIR__ . '/data/cleaning-php-version-after-81.php',
				80100,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before2.php',
				__DIR__ . '/data/cleaning-php-version-after-81.php',
				80200,
			],
			[
				__DIR__ . '/data/cleaning-php-version-before2.php',
				__DIR__ . '/data/cleaning-php-version-after-74.php',
				70400,
			],
			[
				__DIR__ . '/data/cleaning-property-hooks-before.php',
				__DIR__ . '/data/cleaning-property-hooks-after.php',
				80400,
			],
		];
	}

	/**
	 * @dataProvider dataParse
	 */
	public function testParse(
		string $beforeFile,
		string $afterFile,
		int $phpVersionId,
	): void
	{
		$parser = new CleaningParser(
			new SimpleParser(
				new Php8(new Emulative()),
				new NameResolver(),
				new VariadicMethodsVisitor(),
				new VariadicFunctionsVisitor(),
				new PropertyHookNameVisitor(),
			),
			new PhpVersion($phpVersionId),
		);
		$printer = new Printer();
		$ast = $parser->parseFile($beforeFile);
		$this->assertSame(FileReader::read($afterFile), "<?php\n" . $printer->prettyPrint($ast) . "\n");
	}

}
