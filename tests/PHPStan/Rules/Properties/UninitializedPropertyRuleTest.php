<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UninitializedPropertyRule>
 */
class UninitializedPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UninitializedPropertyRule(
			new DirectReadWritePropertiesExtensionProvider([
				new class() implements ReadWritePropertiesExtension {

					public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
					{
						return false;
					}

					public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
					{
						return false;
					}

					public function isInitialized(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === 'UninitializedProperty\\TestExtension' && $propertyName === 'inited';
					}

				},
			]),
			[
				'UninitializedProperty\\TestCase::setUp',
			]
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/uninitialized-property.php'], [
			[
				'Class UninitializedProperty\Foo has an uninitialized property $bar. Give it default value or assign it in the constructor.',
				10,
			],
			[
				'Class UninitializedProperty\Foo has an uninitialized property $baz. Give it default value or assign it in the constructor.',
				12,
			],
			[
				'Access to an uninitialized property UninitializedProperty\Bar::$foo.',
				33,
			],
			[
				'Class UninitializedProperty\Lorem has an uninitialized property $baz. Give it default value or assign it in the constructor.',
				59,
			],
			[
				'Class UninitializedProperty\TestExtension has an uninitialized property $uninited. Give it default value or assign it in the constructor.',
				122,
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/uninitialized-property-promoted.php'], []);
	}

}
