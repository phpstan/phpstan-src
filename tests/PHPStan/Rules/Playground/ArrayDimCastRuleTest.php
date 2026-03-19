<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrayDimCastRule>
 */
final class ArrayDimCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrayDimCastRule();
	}

	public function testRule(): void
	{
		$tip = 'Learn more: <fg=cyan>https://phpstan.org/blog/why-array-string-keys-are-not-type-safe</>';
		$this->analyse([__DIR__ . '/data/array-dim-fetch-cast.php'], [
			[
				"Key '1' (string) will be cast to 1 (int) in the array access.",
				13,
				$tip,
			],
			[
				"Key null (null) will be cast to '' (string) in the array access.",
				14,
				$tip,
			],
			[
				'Key 2.5 (float) will be cast to 2 (int) in the array access.',
				15,
				$tip,
			],
			[
				'Key true (bool) will be cast to 1 (int) in the array access.',
				17,
				$tip,
			],
			[
				'Key false (bool) will be cast to 0 (int) in the array access.',
				18,
				$tip,
			],
			[
				"Key '10' (string) will be cast to 10 (int) in the array access.",
				20,
				$tip,
			],
			[
				"Key '1' (string) will be cast to 1 (int) in the array access.",
				26,
				$tip,
			],
		]);
	}

}
