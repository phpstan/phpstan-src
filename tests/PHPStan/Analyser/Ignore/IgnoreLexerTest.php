<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Testing\PHPStanTestCase;
use function array_pop;
use function substr_count;
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
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
			],
		];

		yield [
			'return.ref, return.ref',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[',', IgnoreLexer::TOKEN_COMMA, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
			],
		];

		yield [
			'return.ref čičí',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				['čičí', IgnoreLexer::TOKEN_OTHER, 1],
			],
		];

		yield [
			'return.ref čičí ',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				['čičí', IgnoreLexer::TOKEN_OTHER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
			],
		];

		yield [
			'return.ref (čičí)',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				['(', IgnoreLexer::TOKEN_OPEN_PARENTHESIS, 1],
				['čičí', IgnoreLexer::TOKEN_OTHER, 1],
				[')', IgnoreLexer::TOKEN_CLOSE_PARENTHESIS, 1],
			],
		];

		yield [
			'return.ref ' . PHP_EOL . ' (čičí)',
			[
				['return.ref', IgnoreLexer::TOKEN_IDENTIFIER, 1],
				[' ', IgnoreLexer::TOKEN_WHITESPACE, 1],
				[PHP_EOL . ' ', IgnoreLexer::TOKEN_END, 1],
				['(', IgnoreLexer::TOKEN_OPEN_PARENTHESIS, 2],
				['čičí', IgnoreLexer::TOKEN_OTHER, 2],
				[')', IgnoreLexer::TOKEN_CLOSE_PARENTHESIS, 2],
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
		$tokens = $lexer->tokenize($input);
		$lastToken = array_pop($tokens);

		$this->assertSame(['', IgnoreLexer::TOKEN_END, substr_count($input, PHP_EOL) + 1], $lastToken);
		$this->assertSame($expectedTokens, $tokens);
	}

}
