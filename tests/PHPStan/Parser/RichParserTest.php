<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\Testing\PHPStanTestCase;
use const PHP_EOL;

class RichParserTest extends PHPStanTestCase
{

	public function dataLinesToIgnore(): iterable
	{
		yield [
			'<?php test();',
			[],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore-line',
			[
				2 => null,
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'// @phpstan-ignore-next-line' . PHP_EOL .
			'test();',
			[
				4 => null,
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore-next-line' . PHP_EOL .
			' */' .
			'test();',
			[
				6 => null,
			],
		];
	}

	/**
	 * @dataProvider dataLinesToIgnore
	 * @param array<int, list<string>|null> $expectedLines
	 */
	public function testLinesToIgnore(string $code, array $expectedLines): void
	{
		/** @var RichParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');
		$ast = $parser->parseString($code);
		$lines = $ast[0]->getAttribute('linesToIgnore');
		$this->assertSame($expectedLines, $lines);
	}

}
