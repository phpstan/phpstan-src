<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\InfiniteRecursionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InfiniteFunctionRecursionRule>
 */
class InfiniteFunctionRecursionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InfiniteFunctionRecursionRule(new InfiniteRecursionFinder(), $this->createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/infinite-function-recursion.php'], [
			[
				'Function InfiniteFunctionRecursion\getWorld() calls itself on every code path, leading to infinite recursion.',
				7,
			],
			[
				'Function InfiniteFunctionRecursion\withSideEffect() calls itself on every code path, leading to infinite recursion.',
				14,
			],
			[
				'Function InfiniteFunctionRecursion\concat() calls itself on every code path, leading to infinite recursion.',
				19,
			],
			[
				'Function InfiniteFunctionRecursion\insideArgument() calls itself on every code path, leading to infinite recursion.',
				24,
			],
		]);
	}

}
