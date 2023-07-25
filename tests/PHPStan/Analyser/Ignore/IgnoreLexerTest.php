<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Testing\PHPStanTestCase;
use const PHP_EOL;

class IgnoreLexerTest extends PHPStanTestCase
{

	public function dataTokenize(): iterable
	{
		yield [
			'',
			[],
		];

		yield [
			'return.ref return.ref',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
			],
		];

		yield [
			'return.ref, return.ref',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[',', IgnoreLexer::TOKEN_COMMA],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
			],
		];

		yield [
			'return.ref čičí',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				['čičí', IgnoreLexer::TOKEN_OTHER],
			],
		];

		yield [
			'return.ref čičí ',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				['čičí', IgnoreLexer::TOKEN_OTHER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
			],
		];

		yield [
			'return.ref (čičí)',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				['(', IgnoreLexer::TOKEN_OPEN_PARENTHESIS],
				['čičí', IgnoreLexer::TOKEN_OTHER],
				[')', IgnoreLexer::TOKEN_CLOSE_PARENTHESIS],
			],
		];

		yield [
			'return.ref ' . PHP_EOL . ' (čičí)',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER],
				[' ', IgnoreLexer::TOKEN_WHITESPACE],
				[PHP_EOL . ' ', IgnoreLexer::TOKEN_EOL],
				['(', IgnoreLexer::TOKEN_OPEN_PARENTHESIS],
				['čičí', IgnoreLexer::TOKEN_OTHER],
				[')', IgnoreLexer::TOKEN_CLOSE_PARENTHESIS],
			],
		];
	}

	/**
	 * @dataProvider dataTokenize
	 * @param list<array{string, IgnoreLexer::TOKEN_*}> $expectedTokens
	 */
	public function testTokenize(string $input, array $expectedTokens): void
	{
		$lexer = new IgnoreLexer();
		$this->assertSame($expectedTokens, $lexer->tokenize($input));
	}

}
