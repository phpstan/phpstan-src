<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function strpos;

/**
 * @extends RuleTestCase<UninitializedPropertyRule>
 */
class UninitializedPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UninitializedPropertyRule(
			new ConstructorsHelper(
				self::getContainer(),
				[
					'UninitializedProperty\\TestCase::setUp',
					'Bug9619\\AdminPresenter::startup',
					'Bug9619\\AdminPresenter2::startup',
					'Bug9619\\AdminPresenter3::startup',
					'Bug9619\\AdminPresenter3::startup2',
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

			// bug-9619
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
						strpos($property->getDocComment() ?? '', '@inject') !== false;
				}

			},
		];
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/uninitialized-property-rule.neon',
		];
	}

	public function testRule(): void
	{
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
			[
				'Class UninitializedProperty\FooTraitClass has an uninitialized property $bar. Give it default value or assign it in the constructor.',
				157,
			],
			[
				'Class UninitializedProperty\FooTraitClass has an uninitialized property $baz. Give it default value or assign it in the constructor.',
				159,
			],
			/*[
				'Access to an uninitialized property UninitializedProperty\InitializedInPublicSetterNonFinalClass::$foo.',
				278,
			],*/
			[
				'Class UninitializedProperty\SometimesInitializedInPrivateSetter has an uninitialized property $foo. Give it default value or assign it in the constructor.',
				286,
			],
			[
				'Access to an uninitialized property UninitializedProperty\SometimesInitializedInPrivateSetter::$foo.',
				303,
			],
			[
				'Class UninitializedProperty\EarlyReturn has an uninitialized property $foo. Give it default value or assign it in the constructor.',
				372,
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/uninitialized-property-promoted.php'], []);
	}

	public function testReadOnly(): void
	{
		// reported by a different rule
		$this->analyse([__DIR__ . '/data/uninitialized-property-readonly.php'], []);
	}

	public function testReadOnlyPhpDoc(): void
	{
		// reported by a different rule
		$this->analyse([__DIR__ . '/data/uninitialized-property-readonly-phpdoc.php'], []);
	}

	public function testBug7219(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7219.php'], [
			[
				'Class Bug7219\Foo has an uninitialized property $id. Give it default value or assign it in the constructor.',
				8,
			],
			[
				'Class Bug7219\Foo has an uninitialized property $email. Give it default value or assign it in the constructor.',
				15,
			],
		]);
	}

	public function testAdditionalConstructorsExtension(): void
	{
		$this->analyse([__DIR__ . '/data/uninitialized-property-additional-constructors.php'], [
			[
				'Class TestInitializedProperty\TestAdditionalConstructor has an uninitialized property $one. Give it default value or assign it in the constructor.',
				07,
			],
			[
				'Class TestInitializedProperty\TestAdditionalConstructor has an uninitialized property $three. Give it default value or assign it in the constructor.',
				11,
			],
		]);
	}

	public function testEfabricaLatteBug(): void
	{
		$this->analyse([__DIR__ . '/data/efabrica-latte-bug.php'], []);
	}

	public function testBug9619(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9619.php'], [
			[
				'Access to an uninitialized property Bug9619\AdminPresenter3::$user.',
				55,
			],
		]);
	}

	public function testBug9831(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9831.php'], [
			[
				'Access to an uninitialized property Bug9831\Foo::$bar.',
				12,
			],
		]);
	}

	public function testRedeclareReadonlyProperties(): void
	{
		$this->analyse([__DIR__ . '/data/redeclare-readonly-property.php'], [
			[
				'Class RedeclareReadonlyProperty\B19 has an uninitialized property $prop2. Give it default value or assign it in the constructor.',
				249,
			],
			[
				'Access to an uninitialized property RedeclareReadonlyProperty\B19::$prop2.',
				260,
			],
		]);
	}

}
