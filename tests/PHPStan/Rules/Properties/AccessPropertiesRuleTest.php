<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
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

	private bool $checkDynamicProperties;

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessPropertiesRule($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, $this->checkThisOnly, $this->checkUnionTypes, false), new PhpVersion($this->phpVersionId), true, $this->checkDynamicProperties);
	}

	public function testAccessProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					24,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					25,
				],
				[
					'Cannot access property $propertyOnString on string.',
					32,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					43,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					44,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$baz.',
					50,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$nonexistent.',
					53,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					59,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					60,
				],
				[
					'Access to property $foo on an unknown class TestAccessProperties\UnknownClass.',
					64,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyBaz.',
					69,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyNonexistent.',
					71,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					77,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					78,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					81,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					84,
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\FirstUnknownClass.',
					147,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\SecondUnknownClass.',
					147,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\WithFooAndBarProperty|TestAccessProperties\WithFooProperty::$bar.',
					177,
				],
				[
					'Access to an undefined property TestAccessProperties\SomeInterface&TestAccessProperties\WithFooProperty::$bar.',
					194,
				],
				[
					'Cannot access property $ipsum on TestAccessProperties\FooAccessProperties|null.',
					208,
				],
				[
					'Cannot access property $foo on null.',
					221,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$lorem.',
					248,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$dolor.',
					251,
				],
				[
					'Cannot access property $bar on TestAccessProperties\NullCoalesce|null.',
					274,
				],
				[
					'Cannot access property $foo on TestAccessProperties\NullCoalesce|null.',
					274,
				],
				[
					'Cannot access property $foo on TestAccessProperties\NullCoalesce|null.',
					274,
				],
				[
					'Access to an undefined property class@anonymous/tests/PHPStan/Rules/Properties/data/access-properties.php:297::$barProperty.',
					302,
				],
				[
					'Cannot access property $selfOrNull on TestAccessProperties\RevertNonNullabilityForIsset|null.',
					407,
				],
			],
		);
	}

	public function testAccessPropertiesWithoutUnionTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = false;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					24,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					25,
				],
				[
					'Cannot access property $propertyOnString on string.',
					32,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					43,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					44,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$baz.',
					50,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$nonexistent.',
					53,
				],
				[
					'Access to private property TestAccessProperties\FooAccessProperties::$foo.',
					59,
				],
				[
					'Access to protected property TestAccessProperties\FooAccessProperties::$bar.',
					60,
				],
				[
					'Access to property $foo on an unknown class TestAccessProperties\UnknownClass.',
					64,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyBaz.',
					69,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$emptyNonexistent.',
					71,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					77,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherNonexistent.',
					78,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					81,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$anotherEmptyNonexistent.',
					84,
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\FirstUnknownClass.',
					147,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to property $test on an unknown class TestAccessProperties\SecondUnknownClass.',
					147,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Access to an undefined property TestAccessProperties\SomeInterface&TestAccessProperties\WithFooProperty::$bar.',
					194,
				],
				[
					'Cannot access property $foo on null.',
					221,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$lorem.',
					248,
				],
				[
					'Access to an undefined property TestAccessProperties\FooAccessProperties::$dolor.',
					251,
				],
				[
					'Cannot access property $bar on TestAccessProperties\NullCoalesce|null.',
					274,
				],
				[
					'Access to an undefined property class@anonymous/tests/PHPStan/Rules/Properties/data/access-properties.php:297::$barProperty.',
					302,
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse(
			[__DIR__ . '/data/access-properties.php'],
			[
				[
					'Access to an undefined property TestAccessProperties\BarAccessProperties::$loremipsum.',
					24,
				],
				[
					'Access to private property $foo of parent class TestAccessProperties\FooAccessProperties.',
					25,
				],
			],
		);
	}

	public function testAccessPropertiesAfterIsNullInBooleanOr(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;

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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3947.php'], []);
	}

	public function testNullSafe(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;

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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3371.php'], []);
	}

	public function testBug4527(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-4527.php'], []);
	}

	public function testBug4808(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-4808.php'], []);
	}

	public function testBug5868(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
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
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-6566.php'], []);
	}

	public function testBug6899(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$errors = [
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
		];
		if (PHP_VERSION_ID >= 80200) {
			$errors[] = [
				'Access to an undefined property object|string::$prop.',
				24,
			];
			$errors[] = [
				'Access to an undefined property object|string::$prop.',
				25,
			];
			$errors[] = [
				'Access to an undefined property object|string::$prop.',
				26,
			];
		}
		$this->analyse([__DIR__ . '/data/bug-6899.php'], $errors);
	}

	public function testBug6026(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-6026.php'], []);
	}

	public function testBug3659(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$errors = [];
		if (PHP_VERSION_ID >= 80200) {
			$errors[] = [
				'Access to an undefined property object::$someProperty.',
				9,
			];
		}
		$this->analyse([__DIR__ . '/data/bug-3659.php'], $errors);
	}

	public function dataDynamicProperties(): array
	{
		$errors = [
			[
				'Access to an undefined property DynamicProperties\Foo::$dynamicProperty.',
				9,
			],
			[
				'Access to an undefined property DynamicProperties\Foo::$dynamicProperty.',
				10,
			],
			[
				'Access to an undefined property DynamicProperties\Foo::$dynamicProperty.',
				11,
			],
			[
				'Access to an undefined property DynamicProperties\Bar::$dynamicProperty.',
				14,
			],
			[
				'Access to an undefined property DynamicProperties\Bar::$dynamicProperty.',
				15,
			],
			[
				'Access to an undefined property DynamicProperties\Bar::$dynamicProperty.',
				16,
			],
		];

		return [
			[false, 80000, []],
			[true, 80000, $errors],
			[false, 80200, $errors],
			[true, 80200, $errors],
		];
	}

	/**
	 * @dataProvider dataDynamicProperties
	 * @param mixed[] $errors
	 */
	public function testDynamicProperties(bool $checkDynamicProperties, int $phpVersionId, array $errors): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = $checkDynamicProperties;
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/dynamic-properties.php'], $errors);
	}

	public function testBug4559(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$errors = [];
		if (PHP_VERSION_ID >= 80200) {
			$errors[] = [
				'Access to an undefined property object::$message.',
				11,
			];
		}
		$this->analyse([__DIR__ . '/data/bug-4559.php'], $errors);
	}

	public function testBug3171(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = false;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3171.php'], []);
	}

	public function testBug3171OnDynamicProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->checkDynamicProperties = true;
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3171.php'], []);
	}

}
