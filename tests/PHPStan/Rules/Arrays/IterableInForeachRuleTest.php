<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<IterableInForeachRule>
 */
class IterableInForeachRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkExplicitMixed = false;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new IterableInForeachRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed));
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

}
