<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnumAncestorsRule>
 */
class EnumAncestorsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumAncestorsRule(
			new GenericAncestorsCheck(
				$this->createReflectionProvider(),
				new GenericObjectTypeCheck(),
				new VarianceCheck(true, true),
				true,
				[],
			),
			new CrossCheckInterfacesHelper(),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/enum-ancestors.php'], [
			[
				'Enum EnumGenericAncestors\Foo has @implements tag, but does not implement any interface.',
				22,
			],
			[
				'PHPDoc tag @implements contains generic type EnumGenericAncestors\NonGeneric<int> but interface EnumGenericAncestors\NonGeneric is not generic.',
				35,
			],
			[
				'Enum EnumGenericAncestors\Foo4 implements generic interface EnumGenericAncestors\Generic but does not specify its types: T, U',
				40,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Generic type EnumGenericAncestors\Generic<stdClass> in PHPDoc tag @implements does not specify all template types of interface EnumGenericAncestors\Generic: T, U',
				56,
			],
			[
				'Enum EnumGenericAncestors\Foo7 has @extends tag, but cannot extend anything.',
				64,
			],
			[
				'Type projection covariant EnumGenericAncestors\NonGeneric in generic type EnumGenericAncestors\Generic<covariant EnumGenericAncestors\NonGeneric, int> in PHPDoc tag @implements is not allowed.',
				93,
			],
		]);
	}

	public function testCrossCheckInterfaces(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/cross-check-interfaces-enums.php'], [
			[
				'Interface IteratorAggregate specifies template type TValue of interface Traversable as string but it\'s already specified as CrossCheckInterfacesEnums\Item.',
				19,
			],
		]);
	}

}
