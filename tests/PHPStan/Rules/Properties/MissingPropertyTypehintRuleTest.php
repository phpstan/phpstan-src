<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingPropertyTypehintRule>
 */
class MissingPropertyTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingPropertyTypehintRule(new MissingTypehintCheck(true, true, true, true, []));
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
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Property MissingPropertyTypehint\Bar::$foo with generic interface MissingPropertyTypehint\GenericInterface does not specify its types: T, U',
				77,
			],
			[
				'Property MissingPropertyTypehint\Bar::$baz with generic class MissingPropertyTypehint\GenericClass does not specify its types: A, B',
				83,
			],
			[
				'Property MissingPropertyTypehint\CallableSignature::$cb type has no signature specified for callable.',
				96,
			],
			[
				'Property MissingPropertyTypehint\NestedArrayInProperty::$args type has no value type specified in iterable type array.',
				106,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

	public function testBug3402(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3402.php'], []);
	}

	public function testPromotedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/promoted-properties-missing-typehint.php'], []);
	}

}
