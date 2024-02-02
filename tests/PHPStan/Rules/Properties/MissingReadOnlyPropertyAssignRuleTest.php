<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function in_array;
use function strpos;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingReadOnlyPropertyAssignRule>
 */
class MissingReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingReadOnlyPropertyAssignRule(
			new ConstructorsHelper(
				self::getContainer(),
				[
					'MissingReadOnlyPropertyAssign\\TestCase::setUp',
					'Bug10523\\Controller::init',
				],
			),
		);
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

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

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

	public function testBug7119(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7119.php'], []);
	}

	public function testBug7314(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7314.php'], []);
	}

	public function testBug8412(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-8412.php'], []);
	}

	public function testBug8958(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-8958.php'], []);
	}

	public function testBug8563(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-8563.php'], []);
	}

	public function testBug6402(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-6402.php'], [
			[
				'Access to an uninitialized readonly property Bug6402\SomeModel2::$views.',
				28,
			],
		]);
	}

	public function testBug7198(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7198.php'], []);
	}

	public function testBug7649(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7649.php'], [
			[
				'Class Bug7649\Foo has an uninitialized readonly property $bar. Assign it in the constructor.',
				7,
			],
		]);
	}

	public function testBug9577(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/../Classes/data/bug-9577.php'], [
			[
				'Class Bug9577\SpecializedException2 has an uninitialized readonly property $message. Assign it in the constructor.',
				8,
			],
		]);
	}

	public function testAnonymousReadonlyClass(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->analyse([__DIR__ . '/data/missing-readonly-anonymous-class-property-assign.php'], [
			[
				'Class class@anonymous/tests/PHPStan/Rules/Properties/data/missing-readonly-anonymous-class-property-assign.php:10 has an uninitialized readonly property $foo. Assign it in the constructor.',
				11,
			],
		]);
	}

	public function testBug10523(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-10523.php'], []);
	}

}
