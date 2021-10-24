<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\MissingTypehintCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingPropertyTypehintRule>
 */
class MissingPropertyTypehintRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new MissingPropertyTypehintRule(new MissingTypehintCheck($broker, true, true, true, []));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-property-typehint.php'], [
			[
				'Property MissingPropertyTypehint\MyClass::$prop1 has no type specified.',
				7,
			],
			[
				'Property MissingPropertyTypehint\MyClass::$prop2 has no type specified.',
				9,
			],
			[
				'Property MissingPropertyTypehint\MyClass::$prop3 has no type specified.',
				14,
			],
			[
				'Property MissingPropertyTypehint\ChildClass::$unionProp type has no value type specified in iterable type array.',
				32,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Property MissingPropertyTypehint\Bar::$foo with generic interface MissingPropertyTypehint\GenericInterface does not specify its types: T, U',
				74,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Property MissingPropertyTypehint\Bar::$baz with generic class MissingPropertyTypehint\GenericClass does not specify its types: A, B',
				80,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Property MissingPropertyTypehint\CallableSignature::$cb type has no signature specified for callable.',
				93,
			],
		]);
	}

	public function testBug3402(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3402.php'], []);
	}

	public function testPromotedProperties(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/promoted-properties-missing-typehint.php'], [
			[
				'Property PromotedPropertiesMissingTypehint\Foo::$lorem has no type specified.',
				15,
			],
			[
				'Property PromotedPropertiesMissingTypehint\Foo::$ipsum type has no value type specified in iterable type array.',
				16,
				MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

}
