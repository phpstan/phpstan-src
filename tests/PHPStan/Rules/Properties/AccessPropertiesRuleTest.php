<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AccessPropertiesRule>
 */
class AccessPropertiesRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkThisOnly;

	/** @var bool */
	private $checkUnionTypes;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new AccessPropertiesRule($broker, new RuleLevelHelper($broker, true, $this->checkThisOnly, $this->checkUnionTypes, false), true);
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
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					264,
				],
				[
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					266,
				],
				[
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					270,
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
					'Access to an undefined property TestAccessProperties\AccessPropertyWithDimFetch::$foo.',
					364,
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
			]
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
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					264,
				],
				[
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					266,
				],
				[
					'Access to an undefined property TestAccessProperties\NullCoalesce::$bar.',
					270,
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
					'Access to an undefined property TestAccessProperties\AccessPropertyWithDimFetch::$foo.',
					364,
				],
				[
					'Access to an undefined property TestAccessProperties\AccessInIsset::$foo.',
					386,
				],
			]
		);
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
					'Access to an undefined property TestAccessProperties\AccessPropertyWithDimFetch::$foo.',
					364,
				],
				[
					'Access to an undefined property TestAccessProperties\AccessInIsset::$foo.',
					386,
				],
			]
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
		]);
	}

}
