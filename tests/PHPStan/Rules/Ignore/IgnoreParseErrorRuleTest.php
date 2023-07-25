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
				'Parse error in @phpstan-ignore: Unexpected comma (,)',
				10,
			],
			[
				'Parse error in @phpstan-ignore: Closing parenthesis ")" before opening parenthesis "("',
				13,
			],
			[
				'Parse error in @phpstan-ignore: Unclosed opening parenthesis "(" without closing parenthesis ")"',
				18,
			],
		]);
	}

	public function testRuleWithUnusedTrait(): void
	{
		$this->analyse([__DIR__ . '/data/ignore-parse-error-trait.php'], [
			[
				'Parse error in @phpstan-ignore: Unexpected comma (,)',
				10,
			],
		]);
	}

}
