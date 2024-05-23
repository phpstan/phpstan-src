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
				'Parse error in @phpstan-ignore: Unexpected comma (,) after comma (,), expected identifier',
				10,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected T_CLOSE_PARENTHESIS after identifier, expected comma (,) or end or T_OPEN_PARENTHESIS',
				13,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected end, unclosed opening parenthesis',
				19,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected T_OTHER \'čičí\' after @phpstan-ignore, expected identifier',
				23,
			],
			[
				'Parse error in @phpstan-ignore: Unexpected end after @phpstan-ignore, expected identifier',
				27,
			],
		]);
	}

	public function testRuleWithUnusedTrait(): void
	{
		$this->analyse([__DIR__ . '/data/ignore-parse-error-trait.php'], [
			[
				'Parse error in @phpstan-ignore: Unexpected comma (,) after comma (,), expected identifier',
				10,
			],
		]);
	}

}
