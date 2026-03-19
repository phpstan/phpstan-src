<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<LiteralArrayKeyCastRule>
 */
final class LiteralArrayKeyCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new LiteralArrayKeyCastRule();
	}

	public function testRule(): void
	{
		$tip = 'Learn more: <fg=cyan>https://phpstan.org/blog/why-array-string-keys-are-not-type-safe</>';
		$this->analyse([__DIR__ . '/data/literal-array-key-cast.php'], [
			[
				"Key '1' (string) will be cast to 1 (int) in the array.",
				14,
				$tip,
			],
			[
				"Key null (null) will be cast to '' (string) in the array.",
				15,
				$tip,
			],
			[
				'Key 2.5 (float) will be cast to 2 (int) in the array.',
				16,
				$tip,
			],
			[
				'Key true (bool) will be cast to 1 (int) in the array.',
				18,
				$tip,
			],
			[
				'Key false (bool) will be cast to 0 (int) in the array.',
				19,
				$tip,
			],
			[
				"Key '10' (string) will be cast to 10 (int) in the array.",
				21,
				$tip,
			],
		]);
	}

}
