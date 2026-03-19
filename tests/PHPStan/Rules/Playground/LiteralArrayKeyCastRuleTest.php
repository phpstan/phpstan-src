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
		$this->analyse([__DIR__ . '/data/literal-array-key-cast.php'], [
			[
				"Key '1' (string) will be cast to 1 (int) in the array.",
				14,
			],
			[
				"Key null (null) will be cast to '' (string) in the array.",
				15,
			],
			[
				'Key 2.5 (float) will be cast to 2 (int) in the array.',
				16,
			],
			[
				'Key true (bool) will be cast to 1 (int) in the array.',
				18,
			],
			[
				'Key false (bool) will be cast to 0 (int) in the array.',
				19,
			],
			[
				"Key '10' (string) will be cast to 10 (int) in the array.",
				21,
			],
		]);
	}

}
