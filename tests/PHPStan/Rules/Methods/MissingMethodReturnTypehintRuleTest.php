<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\MissingTypehintCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingMethodReturnTypehintRule>
 */
class MissingMethodReturnTypehintRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new MissingMethodReturnTypehintRule(new MissingTypehintCheck($broker, true, true));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-method-return-typehint.php'], [
			[
				'Method MissingMethodReturnTypehint\FooInterface::getFoo() has no return typehint specified.',
				8,
			],
			[
				'Method MissingMethodReturnTypehint\FooParent::getBar() has no return typehint specified.',
				15,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::getFoo() has no return typehint specified.',
				25,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::getBar() has no return typehint specified.',
				33,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::unionTypeWithUnknownArrayValueTypehint() return type has no value type specified in iterable type array.',
				46,
			],
			[
				'Method MissingMethodReturnTypehint\Bar::returnsGenericInterface() return type with generic interface MissingMethodReturnTypehint\GenericInterface does not specify its types: T, U',
				79,
			],
			[
				'Method MissingMethodReturnTypehint\Bar::returnsGenericClass() return type with generic class MissingMethodReturnTypehint\GenericClass does not specify its types: A, B',
				89,
			],
		]);
	}

	public function testIndirectInheritanceBug2740(): void
	{
		$this->analyse([__DIR__ . '/data/bug2740.php'], []);
	}

}
