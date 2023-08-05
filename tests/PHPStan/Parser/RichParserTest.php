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
			'test(); // @phpstan-ignore test',
			[
				2 => ['test'],
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
			'test(); // @phpstan-ignore return.ref, return.non (foo)',
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
			'/* @phpstan-ignore test */test();',
			[
				2 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'/** @phpstan-ignore test */test();',
			[
				2 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'  /** @phpstan-ignore test */test();',
			[
				2 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test();  /** @phpstan-ignore test */test();',
			[
				2 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'// @phpstan-ignore test' . PHP_EOL .
			'test();',
			[
				3 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'   // @phpstan-ignore test' . PHP_EOL .
			'test();',
			[
				3 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore test' . PHP_EOL .
			' */' . PHP_EOL .
			'test();',
			[
				6 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore test */' . PHP_EOL .
			'test();',
			[
				4 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore test' . PHP_EOL .
			' */ test();',
			[
				5 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'test(); /**' . PHP_EOL .
			' * @phpstan-ignore test' . PHP_EOL .
			' */',
			[
				3 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore test */' . PHP_EOL,
			[
				3 => ['test'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/** @phpstan-ignore test */',
			[
				3 => ['test'],
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
		$this->assertNull($ast[0]->getAttribute('linesToIgnoreParseErrors'));
	}

	public function dataLinesToIgnoreParseErrors(): iterable
	{
		yield [
			'<?php' . PHP_EOL .
			PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore return.ref,,' . PHP_EOL .
			' *                 return.non,' . PHP_EOL .
			' */',
			[
				4 => ['Unexpected comma (,)'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non )foo',
			[
				2 => ['Closing parenthesis ")" before opening parenthesis "("'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non (foo',
			[
				2 => ['Unclosed opening parenthesis "(" without closing parenthesis ")"'],
			],
		];
	}

	/**
	 * @dataProvider dataLinesToIgnoreParseErrors
	 * @param array<int, non-empty-list<string>> $expectedErrors
	 */
	public function testLinesToIgnoreParseErrors(string $code, array $expectedErrors): void
	{
		/** @var RichParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');
		$ast = $parser->parseString($code);
		$errors = $ast[0]->getAttribute('linesToIgnoreParseErrors');
		$this->assertIsArray($errors);
		$this->assertSame($expectedErrors, $errors);

		$lines = $ast[0]->getAttribute('linesToIgnore');
		$this->assertIsArray($lines);
		$this->assertCount(0, $lines);
	}

}
