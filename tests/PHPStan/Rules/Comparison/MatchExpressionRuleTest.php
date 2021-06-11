<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MatchExpressionRule>
 */
class MatchExpressionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MatchExpressionRule(true);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/match-expr.php'], [
			[
				'Match arm comparison between 1|2|3 and \'foo\' is always false.',
				14,
			],
			[
				'Match arm comparison between 1|2|3 and 0 is always false.',
				19,
			],
			[
				'Match arm comparison between 3 and 3 is always true.',
				28,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				29,
			],
			[
				'Match arm comparison between 3 and 3 is always true.',
				35,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				36,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				40,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				41,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				42,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				46,
			],
			[
				'Match arm is unreachable because previous comparison is always true.',
				47,
			],
			[
				'Match expression does not handle remaining value: 3',
				50,
			],
			[
				'Match expression does not handle remaining values: 1|2|3',
				55,
			],
			[
				'Match arm comparison between 1|2 and 3 is always false.',
				65,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				70,
			],
			[
				'Match arm comparison between true and false is always false.',
				86,
			],
			[
				'Match arm comparison between true and false is always false.',
				92,
			],
			[
				'Match expression does not handle remaining value: true',
				90,
			],
		]);
	}

	public function testBug5161(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

}
