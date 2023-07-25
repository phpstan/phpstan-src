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

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore',
			[
				2 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref',
			[
				2 => ['return.ref'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref return.non',
			[
				2 => ['return.ref', 'return.non'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non',
			[
				2 => ['return.ref', 'return.non'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non (foo',
			[
				2 => ['return.ref', 'return.non'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non čičí',
			[
				2 => ['return.ref', 'return.non'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'/* @phpstan-ignore */test();',
			[
				2 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'/** @phpstan-ignore */test();',
			[
				2 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'  /** @phpstan-ignore */test();',
			[
				2 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test();  /** @phpstan-ignore */test();',
			[
				2 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'// @phpstan-ignore' . PHP_EOL .
			'test();',
			[
				3 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'   // @phpstan-ignore' . PHP_EOL .
			'test();',
			[
				3 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore' . PHP_EOL .
			' */' . PHP_EOL .
			'test();',
			[
				6 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore */' . PHP_EOL .
			'test();',
			[
				4 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore' . PHP_EOL .
			' */ test();',
			[
				5 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'test(); /**' . PHP_EOL .
			' * @phpstan-ignore' . PHP_EOL .
			' */',
			[
				3 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore */' . PHP_EOL,
			[
				3 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore */',
			[
				3 => [],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore return.ref,' . PHP_EOL .
			' *                 return.non,' . PHP_EOL .
			' */',
			[
				6 => ['return.ref', 'return.non'],
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
