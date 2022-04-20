<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessPropertiesRule>
 */
class AccessPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	private bool $checkUnionTypes;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessPropertiesRule($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, $this->checkThisOnly, $this->checkUnionTypes, false), true);
	}

	public function testAccessProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					23,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					24,
				],
				[
					'Cannot access property $propertyOnString on string.',
					31,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					42,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					43,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$baz.',
					49,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$nonexistent.',
					52,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					58,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					59,
				],
				[
					'Access to property $foo on an unknown class TestAccessProperties\UnknownClass.',
					63,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyBaz.',
					68,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyNonexistent.',
					70,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					76,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					77,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					80,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					83,
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\FirstUnknownClass.',
					146,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\SecondUnknownClass.',
					146,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\WithFooAndBarProperty|TestAccessProperties\WithFooProperty::$bar.',
					176,
				],
				[
					'Access to an undefined property TestAccessProperties\SomeInterface&TestAccessProperties\WithFooProperty::$bar.',
					193,
				],
				[
					'Cannot access property $ipsum on TestAccessProperties\FooAccessProperties|null.',
					207,
				],
				[
					'Cannot access property $foo on null.',
					220,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$lorem.',
					247,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$dolor.',
					250,
				],
				[
					'Cannot access property $bar on TestAccessProperties\NullCoalesce|null.',
					272,
				],
				[
					'Cannot access property $foo on TestAccessProperties\NullCoalesce|null.',
					272,
				],
				[
					'Cannot access property $foo on TestAccessProperties\NullCoalesce|null.',
					272,
				],
				[
					'Access to an undefined property class@anonymous/tests/PHPStan/Rules/Properties/data/access-properties.php:294::$barProperty.',
					299,
				],
				[
					'Access to an undefined property TestAccessProperties\AccessInIsset::$foo.',
					386,
				],
				[
					'Cannot access property $selfOrNull on TestAccessProperties\RevertNonNullabilityForIsset|null.',
					402,
				],
				[
					'Cannot access property $array on stdClass|null.',
					412,
				],
			],
		);
	}

	public function testAccessPropertiesWithoutUnionTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = false;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					23,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					24,
				],
				[
					'Cannot access property $propertyOnString on string.',
					31,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					42,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					43,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$baz.',
					49,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$nonexistent.',
					52,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					58,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					59,
				],
				[
					'Access to property $foo on an unknown class TestAccessProperties\UnknownClass.',
					63,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyBaz.',
					68,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyNonexistent.',
					70,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					76,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					77,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					80,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					83,
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\FirstUnknownClass.',
					146,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\SecondUnknownClass.',
					146,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\SomeInterface&TestAccessProperties\WithFooProperty::$bar.',
					193,
				],
				[
					'Cannot access property $foo on null.',
					220,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$lorem.',
					247,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$dolor.',
					250,
				],
				[
					'Cannot access property $bar on TestAccessProperties\NullCoalesce|null.',
					272,
				],
				[
					'Access to an undefined property class@anonymous/tests/PHPStan/Rules/Properties/data/access-properties.php:294::$barProperty.',
					299,
				],
				[
					'Access to an undefined property TestAccessProperties\AccessInIsset::$foo.',
					386,
				],
			],
		);
	}

	public function testRuleAssignOp(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/access-properties-assign-op.php'], [
			[
				'Access to an undefined property TestAccessProperties\AssignOpNonexistentProperty::$flags.',
				10,
			],
		]);
	}

	public function testAccessPropertiesOnThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->checkUnionTypes = true;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					23,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					24,
				],
				[
					'Access to an undefined property TestAccessProperties\AccessInIsset::$foo.',
					386,
				],
			],
		);
	}

	public function testAccessPropertiesAfterIsNullInBooleanOr(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/access-properties-after-isnull.php'], [
			[
				'Cannot access property $fooProperty on null.',
				16,
			],
			[
				'Cannot access property $fooProperty on null.',
				25,
			],
			[
				'Access to an undefined property AccessPropertiesAfterIsNull\Foo::$barProperty.',
				28,
			],
			[
				'Access to an undefined property AccessPropertiesAfterIsNull\Foo::$barProperty.',
				31,
			],
			[
				'Cannot access property $fooProperty on null.',
				35,
			],
			[
				'Cannot access property $fooProperty on null.',
				44,
			],
			[
				'Access to an undefined property AccessPropertiesAfterIsNull\Foo::$barProperty.',
				47,
			],
			[
				'Access to an undefined property AccessPropertiesAfterIsNull\Foo::$barProperty.',
				50,
			],
		]);
	}

	public function testDateIntervalChildProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/date-interval-child-properties.php'], [
			[
				'Access to an undefined property AccessPropertiesDateIntervalChild\DateIntervalChild::$nonexistent.',
				14,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/access-properties-class-exists.php'], [
			[
				'Access to property $lorem on an unknown class AccessPropertiesClassExists\Bar.',
				15,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to property $lorem on an unknown class AccessPropertiesClassExists\Baz.',
				15,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to property $lorem on an unknown class AccessPropertiesClassExists\Baz.',
				18,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to property $lorem on an unknown class AccessPropertiesClassExists\Bar.',
				22,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testMixin(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/mixin.php'], [
			[
				'Access to an undefined property MixinProperties\GenericFoo<ReflectionClass>::$namee.',
				51,
			],
		]);
	}

	public function testBug3947(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3947.php'], []);
	}

	public function testNullSafe(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch.php'], [
			[
				'Access to an undefined property NullsafePropertyFetch\Foo::$baz.',
				13,
			],
			[
				'Cannot access property $bar on string.',
				18,
			],
			[
				'Cannot access property $bar on string.',
				19,
			],
			[
				'Cannot access property $bar on string.',
				21,
			],
			[
				'Cannot access property $bar on string.',
				22,
			],
		]);
	}

	public function testBug3371(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3371.php'], []);
	}

	public function testBug4527(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4527.php'], []);
	}

	public function testBug4808(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4808.php'], []);
	}


	public function testBug5868(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5868.php'], [
			[
				'Cannot access property $child on Bug5868PropertyFetch\Foo|null.',
				31,
			],
			[
				'Cannot access property $child on Bug5868PropertyFetch\Child|null.',
				32,
			],
			[
				'Cannot access property $existingChild on Bug5868PropertyFetch\Child|null.',
				33,
			],
			[
				'Cannot access property $existingChild on Bug5868PropertyFetch\Child|null.',
				34,
			],
		]);
	}

	public function testBug6385(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-6385.php'], [
			[
				'Access to an undefined property UnitEnum::$value.',
				43,
			],
			[
				'Access to an undefined property Bug6385\ActualUnitEnum::$value.',
				47,
			],
		]);
	}

	public function testBug6566(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-6566.php'], []);
	}

	public function testBug6899(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-6899.php'], [
			[
				'Cannot access property $prop on string.',
				13,
			],
			[
				'Cannot access property $prop on string.',
				14,
			],
			[
				'Cannot access property $prop on string.',
				15,
			],
		]);
	}

	public function testBug6026(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-6026.php'], []);
	}

	public function testBug3659(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3659.php'], []);
	}

	public function testDynamicProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/dynamic-properties.php'], []);
	}

}
