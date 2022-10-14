<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AllowedSubTypesRule>
 */
class AllowedSubTypesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AllowedSubTypesRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/allowed-sub-types.php'], [
			[
				'Type AllowedSubTypes\\Baz is not allowed to be a subtype of AllowedSubTypes\\Foo.',
				11,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
			__DIR__ . '/data/allowed-sub-types.neon',
		];
	}

}
