<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function in_array;
use function strpos;

/**
 * @extends RuleTestCase<MissingReadOnlyPropertyAssignRule>
 */
class MissingReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	private bool $shouldNarrowMethodScopeFromConstructor = false;

	protected function getRule(): Rule
	{
		return new MissingReadOnlyPropertyAssignRule(
			new ConstructorsHelper(
				self::getContainer(),
				[
					'MissingReadOnlyPropertyAssign\\TestCase::setUp',
					'Bug10523\\Controller::init',
					'Bug10523\\MultipleWrites::init',
					'Bug10523\\SingleWriteInConstructorCalledMethod::init',
				],
			),
		);
	}

	public function shouldNarrowMethodScopeFromConstructor(): bool
	{
		return $this->shouldNarrowMethodScopeFromConstructor;
	}

	protected function getReadWritePropertiesExtensions(): array
	{
		return [
			new class() implements ReadWritePropertiesExtension {

				public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
				{
					return $this->isEntityId($property, $propertyName);
				}

				public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
				{
					return $this->isEntityId($property, $propertyName);
				}

				public function isInitialized(PropertyReflection $property, string $propertyName): bool
				{
					return $this->isEntityId($property, $propertyName);
				}

				private function isEntityId(PropertyReflection $property, string $propertyName): bool
				{
					return $property->getDeclaringClass()->getName() === 'MissingReadOnlyPropertyAssign\\Entity'
						&& in_array($propertyName, ['id'], true);
				}

			},
			new class() implements ReadWritePropertiesExtension {

				public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
				{
					return false;
				}

				public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
				{
					return $this->isInitialized($property, $propertyName);
				}

				public function isInitialized(PropertyReflection $property, string $propertyName): bool
				{
					return $property->isPublic() &&
						strpos($property->getDocComment() ?? '', '@init') !== false;
				}

			},
		];
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-readonly-property-assign.php'], [
			[
				'Class MissingReadOnlyPropertyAssign\Foo has an uninitialized readonly property $unassigned. Assign it in the constructor.',
				14,
			],
			[
				'Class MissingReadOnlyPropertyAssign\Foo has an uninitialized readonly property $unassigned2. Assign it in the constructor.',
				16,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\Foo::$readBeforeAssigned.',
				33,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssign\Foo::$doubleAssigned is already assigned.',
				37,
			],
			[
				'Class MissingReadOnlyPropertyAssign\BarDoubleAssignInSetter has an uninitialized readonly property $foo. Assign it in the constructor.',
				53,
			],
			[
				'Class MissingReadOnlyPropertyAssign\AssignOp has an uninitialized readonly property $foo. Assign it in the constructor.',
				79,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\AssignOp::$foo.',
				85,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\AssignOp::$bar.',
				87,
			],
			[
				'Class MissingReadOnlyPropertyAssign\FooTraitClass has an uninitialized readonly property $unassigned. Assign it in the constructor.',
				114,
			],
			[
				'Class MissingReadOnlyPropertyAssign\FooTraitClass has an uninitialized readonly property $unassigned2. Assign it in the constructor.',
				116,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\FooTraitClass::$readBeforeAssigned.',
				145,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssign\FooTraitClass::$doubleAssigned is already assigned.',
				149,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssign\AdditionalAssignOfReadonlyPromotedProperty::$x is already assigned.',
				188,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\MethodCalledFromConstructorBeforeAssign::$foo.',
				226,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\MethodCalledTwice::$foo.',
				244,
			],
			[
				'Class MissingReadOnlyPropertyAssign\PropertyAssignedOnDifferentObjectUninitialized has an uninitialized readonly property $foo. Assign it in the constructor.',
				264,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7119(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7119.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7314(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7314.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8412(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8412.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8958(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8958.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8563(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8563.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug6402(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6402.php'], [
			[
				'Access to an uninitialized readonly property Bug6402\SomeModel2::$views.',
				28,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7198(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7198.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7649(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7649.php'], [
			[
				'Class Bug7649\Foo has an uninitialized readonly property $bar. Assign it in the constructor.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9577(): void
	{
		$this->analyse([__DIR__ . '/../Classes/data/bug-9577.php'], [
			[
				'Class Bug9577\SpecializedException2 has an uninitialized readonly property $message. Assign it in the constructor.',
				8,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testAnonymousReadonlyClass(): void
	{
		$this->analyse([__DIR__ . '/data/missing-readonly-anonymous-class-property-assign.php'], [
			[
				'Class class@anonymous/tests/PHPStan/Rules/Properties/data/missing-readonly-anonymous-class-property-assign.php:10 has an uninitialized readonly property $foo. Assign it in the constructor.',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10523(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10523.php'], [
			[
				'Readonly property Bug10523\MultipleWrites::$userAccount is already assigned.',
				55,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10822(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10822.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRedeclaredReadonlyProperties(): void
	{
		$this->analyse([__DIR__ . '/data/redeclare-readonly-property.php'], [
			[
				'Readonly property RedeclareReadonlyProperty\B1::$myProp is already assigned.',
				16,
			],
			[
				'Readonly property RedeclareReadonlyProperty\B5::$myProp is already assigned.',
				50,
			],
			[
				'Readonly property RedeclareReadonlyProperty\B7::$myProp is already assigned.',
				70,
			],
			[
				'Readonly property RedeclareReadonlyProperty\A@anonymous/tests/PHPStan/Rules/Properties/data/redeclare-readonly-property.php:117::$myProp is already assigned.',
				121,
			],
			[
				'Class RedeclareReadonlyProperty\B16 has an uninitialized readonly property $myProp. Assign it in the constructor.',
				195,
			],
			[
				'Class RedeclareReadonlyProperty\C17 has an uninitialized readonly property $aProp. Assign it in the constructor.',
				218,
			],
			[
				'Class RedeclareReadonlyProperty\B18 has an uninitialized readonly property $aProp. Assign it in the constructor.',
				233,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testRedeclaredPropertiesOfReadonlyClass(): void
	{
		$this->analyse([__DIR__ . '/data/redeclare-property-of-readonly-class.php'], [
			[
				'Readonly property RedeclarePropertyOfReadonlyClass\B1::$promotedProp is already assigned.',
				15,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8101(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8101.php'], [
			[
				'Readonly property Bug8101\B::$myProp is already assigned.',
				12,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9863(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9863.php'], [
			[
				'Readonly property Bug9863\ReadonlyChildWithoutIsset::$foo is already assigned.',
				17,
			],
			[
				'Class Bug9863\ReadonlyParentWithIsset has an uninitialized readonly property $foo. Assign it in the constructor.',
				23,
			],
			[
				'Access to an uninitialized readonly property Bug9863\ReadonlyParentWithIsset::$foo.',
				28,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10048(): void
	{
		$this->shouldNarrowMethodScopeFromConstructor = true;
		$this->analyse([__DIR__ . '/data/bug-10048.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug11828(): void
	{
		$this->shouldNarrowMethodScopeFromConstructor = true;
		$this->analyse([__DIR__ . '/data/bug-11828.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9864(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9864.php'], []);
	}

}
