<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
				'Match arm comparison between 1|2 and 3 is always false.',
				61,
			],
			[
				'Match arm comparison between 1 and 1 is always true.',
				66,
			],
			[
				'Match expression does not handle remaining values: 1|2|3',
				78,
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
			[
				'Match expression does not handle remaining values: int<min, 0>|int<2, max>',
				168,
			],
		]);
	}

	public function testBug5161(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

	public function testBug4857(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4857.php'], [
			[
				'Match expression does not handle remaining value: true',
				13,
			],
			[
				'Match expression does not handle remaining value: true',
				23,
			],
		]);
	}

	public function testBug5454(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-5454.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/match-enums.php'], [
			[
				'Match expression does not handle remaining values: MatchEnums\Foo::THREE|MatchEnums\Foo::TWO',
				19,
			],
			[
				'Match expression does not handle remaining values: ($this(MatchEnums\Foo)&MatchEnums\Foo::TWO)|($this(MatchEnums\Foo)&MatchEnums\Foo::THREE)',
				35,
			],
			[
				'Match expression does not handle remaining value: MatchEnums\Foo::THREE',
				56,
			],
			[
				'Match arm comparison between *NEVER* and MatchEnums\Foo is always false.',
				77,
			],
		]);
	}

	public function testBug6394(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-6394.php'], []);
	}

	public function testBug6115(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6115.php'], [
			[
				'Match expression does not handle remaining value: 3',
				32,
			],
		]);
	}

	public function testBug7095(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7095.php'], []);
	}

	public function testBug7176(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-7176.php'], []);
	}

	public function testBug6064(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-6064.php'], []);
	}

	public function testBug6647(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-6647.php'], []);
	}

}
