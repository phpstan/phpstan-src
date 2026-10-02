<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TypeTraverserMapMemoizedRule>
 */
final class TypeTraverserMapMemoizedRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TypeTraverserMapMemoizedRule(self::getContainer()->getByType(FileHelper::class), false);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/type-traverser-map-memoized.php'], [
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				19,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				30,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				37,
			],
			[
				'Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.',
				53,
			],
			[
				'Callback of TypeTraverser::mapMemoized() appends to $types captured by reference, so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				168,
			],
			[
				'Callback of TypeTraverser::mapMemoized() reads $found captured by reference that it also writes, so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				180,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/type-traverser-map-memoized.php', __DIR__ . '/data/type-traverser-map-memoized.php.fixed');
	}

}
