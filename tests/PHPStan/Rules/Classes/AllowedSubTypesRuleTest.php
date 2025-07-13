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

	public function testSealed(): void
	{
		$this->analyse([__DIR__ . '/data/sealed.php'], [
			[
				'Type Sealed\BazClass is not allowed to be a subtype of Sealed\BaseClass.',
				11,
			],
			[
				'Type Sealed\BazClass2 is not allowed to be a subtype of Sealed\BaseInterface.',
				19,
			],
			[
				'Type Sealed\BazInterface is not allowed to be a subtype of Sealed\BaseInterface2.',
				27,
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
