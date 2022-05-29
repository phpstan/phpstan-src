<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function in_array;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingReadOnlyPropertyAssignRule>
 */
class MissingReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingReadOnlyPropertyAssignRule(
			new ConstructorsHelper([
				'MissingReadOnlyPropertyAssign\\TestCase::setUp',
			]),
			new DirectReadWritePropertiesExtensionProvider([
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
			]),
		);
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
		]);
	}

	public function testBug7314(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7314.php'], []);
	}

}
