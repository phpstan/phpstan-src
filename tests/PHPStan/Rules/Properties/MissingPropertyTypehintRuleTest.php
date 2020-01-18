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
		return new MissingPropertyTypehintRule(new MissingTypehintCheck($broker, true, true));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-property-typehint.php'], [
			[
				'Property MissingPropertyTypehint\MyClass::$prop1 has no typehint specified.',
				7,
			],
			[
				'Property MissingPropertyTypehint\MyClass::$prop2 has no typehint specified.',
				9,
			],
			[
				'Property MissingPropertyTypehint\MyClass::$prop3 has no typehint specified.',
				14,
			],
			[
				'Property MissingPropertyTypehint\ChildClass::$unionProp type has no value type specified in iterable type array.',
				32,
				"Consider adding something like <fg=cyan>array<Foo></> to the PHPDoc.\nYou can turn off this check by setting <fg=cyan>checkMissingIterableValueType: false</> in your <fg=cyan>%configurationFile%</>.",
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
		]);
	}

}
