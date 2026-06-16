<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\InfiniteRecursionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InfiniteMethodRecursionRule>
 */
class InfiniteMethodRecursionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InfiniteMethodRecursionRule(new InfiniteRecursionFinder());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/infinite-method-recursion.php'], [
			[
				'Method InfiniteMethodRecursion\HelloWorld::getWorld() calls itself on every code path, leading to infinite recursion.',
				13,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::withSideEffect() calls itself on every code path, leading to infinite recursion.',
				20,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::concat() calls itself on every code path, leading to infinite recursion.',
				25,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::coalesceLeft() calls itself on every code path, leading to infinite recursion.',
				30,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::assignThenReturn() calls itself on every code path, leading to infinite recursion.',
				35,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::insideArgument() calls itself on every code path, leading to infinite recursion.',
				42,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::staticSelf() calls itself on every code path, leading to infinite recursion.',
				47,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::staticLateSelf() calls itself on every code path, leading to infinite recursion.',
				52,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::staticClassName() calls itself on every code path, leading to infinite recursion.',
				57,
			],
			[
				'Method InfiniteMethodRecursion\HelloWorld::__construct() calls itself on every code path, leading to infinite recursion.',
				105,
			],
		]);
	}

}
