<?php declare(strict_types = 1);

namespace PHPStan\Rules\Ignore;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IgnoreParseErrorRule>
 */
class IgnoreParseErrorRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IgnoreParseErrorRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/ignore-parse-error.php'], [
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_COMMA after T_COMMA, expected T_IDENTIFIER',
				10,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_CLOSE_PARENTHESIS after T_IDENTIFIER, expected T_COMMA or T_END or T_OPEN_PARENTHESIS',
				13,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_END, unclosed opening parenthesis',
				19,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_OTHER \'čičí\' after start, expected T_IDENTIFIER',
				23,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_END after start, expected T_IDENTIFIER',
				27,
			],
		]);
	}

	public function testRuleWithUnusedTrait(): void
	{
		$this->analyse([__DIR__ . '/data/ignore-parse-error-trait.php'], [
			[
				'Parse error in @phpstan-ignore: Unexpected token type T_COMMA after T_COMMA, expected T_IDENTIFIER',
				10,
			],
		]);
	}

}
