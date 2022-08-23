<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<IterableInForeachRule>
 */
class IterableInForeachRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		return new IterableInForeachRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, false));
	}

	public function testCheckWithMaybes(): void
	{
		$this->analyse([__DIR__ . '/data/foreach-iterable.php'], [
			[
				'Argument of an invalid type string supplied for foreach, only iterables are supported.',
				10,
			],
			[
				'Argument of an invalid type array<int, int>|false supplied for foreach, only iterables are supported.',
				19,
			],
			[
				'Iterating over an object of an unknown class IterablesInForeach\Bar.',
				47,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug5744(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5744.php'], [
			/*[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				15,
			],*/
			[
				'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
				28,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/foreach-iterable-nullsafe.php'], [
			[
				'Argument of an invalid type array<int>|null supplied for foreach, only iterables are supported.',
				14,
			],
		]);
	}

	public function testBug6564(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6564.php'], []);
	}

}
