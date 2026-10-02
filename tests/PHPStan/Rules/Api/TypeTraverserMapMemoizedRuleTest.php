<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TypeTraverserMapMemoizedRule>
 */
final class TypeTraverserMapMemoizedRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TypeTraverserMapMemoizedRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/type-traverser-map-memoized.php'], [
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				20,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				31,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				38,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				54,
			],
			[
				'Callback of TypeTraverser::mapMemoized() appends to $types captured by reference, so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				169,
			],
			[
				'Callback of TypeTraverser::mapMemoized() reads $found captured by reference that it also writes, so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				181,
			],
			[
				'Callback of TypeTraverser::mapMemoized() writes to $types captured by reference by spl_object_id(), so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				196,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/type-traverser-map-memoized.php', __DIR__ . '/data/type-traverser-map-memoized.php.fixed');
	}

}
