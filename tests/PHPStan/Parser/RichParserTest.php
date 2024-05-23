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
			'test(); // @phpstan-ignore return.ref, return.non (foo, because...)',
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
			'// @phpstan-ignore test' . PHP_EOL .
			'test(); // @phpstan-ignore test',
			[
				3 => ['test', 'test'],
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
			'test(); // @phpstan-ignore identifier (comment), identifier2 (comment2)' . PHP_EOL,
			[
				2 => ['identifier', 'identifier2'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			"test(); // @phpstan-ignore  identifier (comment1),\tidentifier2 ,  identifier3 (comment3) " . PHP_EOL,
			[
				2 => ['identifier', 'identifier2', 'identifier3'],
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
		$this->assertNull($ast[0]->getAttribute('linesToIgnoreParseErrors'));
		$this->assertSame($expectedLines, $lines);
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
				4 => ['Unexpected comma (,) after comma (,), expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore return.ref,' . PHP_EOL .
			' */',
			[
				3 => ['Unexpected end after comma (,), expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'/**' . PHP_EOL .
			' * @phpstan-ignore' . PHP_EOL .
			' */',
			[
				3 => ['Unexpected end after @phpstan-ignore, expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref,' . PHP_EOL,
			[
				2 => ['Unexpected end after comma (,), expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore test (comment),' . PHP_EOL,
			[
				2 => ['Unexpected end after comma (,), expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore ,' . PHP_EOL,
			[
				2 => ['Unexpected comma (,) after @phpstan-ignore, expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non )foo',
			[
				2 => ['Unexpected T_CLOSE_PARENTHESIS after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non (foo',
			[
				2 => ['Unexpected end, unclosed opening parenthesis'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore ()' . PHP_EOL,
			[
				2 => ['Unexpected T_OPEN_PARENTHESIS after @phpstan-ignore, expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore ' . PHP_EOL,
			[
				2 => ['Unexpected end after @phpstan-ignore, expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore ,' . PHP_EOL,
			[
				2 => ['Unexpected comma (,) after @phpstan-ignore, expected identifier'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore čumim' . PHP_EOL,
			[
				2 => ["Unexpected T_OTHER 'čumim' after @phpstan-ignore, expected identifier"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore test ((inner)' . PHP_EOL,
			[
				2 => ['Unexpected end, unclosed opening parenthesis'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref return.non',
			[
				2 => ['Unexpected identifier after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore identifier (comment))' . PHP_EOL,
			[
				2 => ['Unexpected T_CLOSE_PARENTHESIS after T_CLOSE_PARENTHESIS, expected comma (,) or end'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore mečoun two' . PHP_EOL,
			[
				2 => ["Unexpected T_OTHER 'čoun' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore mečoun' . PHP_EOL,
			[
				2 => ["Unexpected T_OTHER 'čoun' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore return.ref, return.non čičí',
			[
				2 => ["Unexpected T_OTHER 'čičí' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore identifier -- comment' . PHP_EOL, // phpcs comment style
			[
				2 => ["Unexpected T_OTHER '--' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore identifier [comment]' . PHP_EOL,
			[
				2 => ["Unexpected T_OTHER '[comment]' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore identifier (comment) (comment2)' . PHP_EOL,
			[
				2 => ['Unexpected T_OPEN_PARENTHESIS after T_CLOSE_PARENTHESIS, expected comma (,) or end'],
			],
		];

		yield [
			'<?php' . PHP_EOL .
			'test(); // @phpstan-ignore https://example.com' . PHP_EOL,
			[
				2 => ["Unexpected T_OTHER '://example.com' after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS"],
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
				4 => ['Unexpected end after comma (,), expected identifier'],
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
