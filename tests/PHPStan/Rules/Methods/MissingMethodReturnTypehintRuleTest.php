<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingMethodReturnTypehintRule>
 */
class MissingMethodReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMethodReturnTypehintRule(new MissingTypehintCheck(true, true, true, true, []));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-method-return-typehint.php'], [
			[
				'Method MissingMethodReturnTypehint\FooInterface::getFoo() has no return type specified.',
				8,
			],
			[
				'Method MissingMethodReturnTypehint\FooParent::getBar() has no return type specified.',
				15,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::getFoo() has no return type specified.',
				25,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::getBar() has no return type specified.',
				33,
			],
			[
				'Method MissingMethodReturnTypehint\Foo::unionTypeWithUnknownArrayValueTypehint() return type has no value type specified in iterable type array.',
				46,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Method MissingMethodReturnTypehint\Bar::returnsGenericInterface() return type with generic interface MissingMethodReturnTypehint\GenericInterface does not specify its types: T, U',
				79,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Method MissingMethodReturnTypehint\Bar::returnsGenericClass() return type with generic class MissingMethodReturnTypehint\GenericClass does not specify its types: A, B',
				89,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Method MissingMethodReturnTypehint\CallableSignature::doFoo() return type has no signature specified for callable.',
				99,
			],
		]);
	}

	public function testIndirectInheritanceBug2740(): void
	{
		$this->analyse([__DIR__ . '/data/bug2740.php'], []);
	}

	public function testArrayTypehintWithoutNullInPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/array-typehint-without-null-in-phpdoc.php'], []);
	}

	public function testBug4415(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4415.php'], []);
	}

	public function testBug5089(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5089.php'], []);
	}

	public function testBug5436(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5436.php'], []);
	}

	public function testBug4758(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4758.php'], []);
	}

	public function testBug9571(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9571.php'], []);
	}

	public function testBug9571PhpDocs(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9571-phpdocs.php'], []);
	}

}
