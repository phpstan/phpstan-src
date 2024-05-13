<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingMethodParameterTypehintRule>
 */
class MissingMethodParameterTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMethodParameterTypehintRule(new MissingTypehintCheck(true, true, true, true, []), true);
	}

	public function testRule(): void
	{
		$errors = [
			[
				'Method MissingMethodParameterTypehint\FooInterface::getFoo() has parameter $p1 with no type specified.',
				8,
			],
			[
				'Method MissingMethodParameterTypehint\FooParent::getBar() has parameter $p2 with no type specified.',
				15,
			],
			[
				'Method MissingMethodParameterTypehint\Foo::getFoo() has parameter $p1 with no type specified.',
				25,
			],
			[
				'Method MissingMethodParameterTypehint\Foo::getBar() has parameter $p2 with no type specified.',
				33,
			],
			[
				'Method MissingMethodParameterTypehint\Foo::getBaz() has parameter $p3 with no type specified.',
				42,
			],
			[
				'Method MissingMethodParameterTypehint\Foo::unionTypeWithUnknownArrayValueTypehint() has parameter $a with no value type specified in iterable type array.',
				58,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Method MissingMethodParameterTypehint\Bar::acceptsGenericInterface() has parameter $i with generic interface MissingMethodParameterTypehint\GenericInterface but does not specify its types: T, U',
				91,
			],
			[
				'Method MissingMethodParameterTypehint\Bar::acceptsGenericClass() has parameter $c with generic class MissingMethodParameterTypehint\GenericClass but does not specify its types: A, B',
				101,
			],
			[
				'Method MissingMethodParameterTypehint\CollectionIterableAndGeneric::acceptsCollection() has parameter $collection with generic interface DoctrineIntersectionTypeIsSupertypeOf\Collection but does not specify its types: TKey, T',
				111,
			],
			[
				'Method MissingMethodParameterTypehint\CollectionIterableAndGeneric::acceptsCollection2() has parameter $collection with generic interface DoctrineIntersectionTypeIsSupertypeOf\Collection but does not specify its types: TKey, T',
				119,
			],
			[
				'Method MissingMethodParameterTypehint\CallableSignature::doFoo() has parameter $cb with no signature specified for callable.',
				180,
			],
			[
				'Method MissingMethodParameterTypehint\MissingParamOutType::oneArray() has @param-out PHPDoc tag for parameter $a with no value type specified in iterable type array.',
				207,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Method MissingMethodParameterTypehint\MissingParamOutType::generics() has @param-out PHPDoc tag for parameter $a with generic class ReflectionClass but does not specify its types: T',
				215,
			],
			[
				'Method MissingMethodParameterTypehint\MissingParamClosureThisType::generics() has @param-closure-this PHPDoc tag for parameter $cb with generic class ReflectionClass but does not specify its types: T',
				226,
			],
			[
				'Method MissingMethodParameterTypehint\MissingPureClosureSignatureType::doFoo() has parameter $cb with no signature specified for Closure.',
				238,
			],
		];

		$this->analyse([__DIR__ . '/data/missing-method-parameter-typehint.php'], $errors);
	}

	public function testPromotedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/missing-typehint-promoted-properties.php'], [
			[
				'Method MissingTypehintPromotedProperties\Foo::__construct() has parameter $foo with no value type specified in iterable type array.',
				8,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Method MissingTypehintPromotedProperties\Bar::__construct() has parameter $foo with no value type specified in iterable type array.',
				21,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

	public function testDeepInspectTypes(): void
	{
		$this->analyse([__DIR__ . '/data/deep-inspect-types.php'], [
			[
				'Method DeepInspectTypes\Foo::doFoo() has parameter $foo with no value type specified in iterable type iterable.',
				11,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Method DeepInspectTypes\Foo::doBar() has parameter $bars with generic class DeepInspectTypes\Bar but does not specify its types: T',
				17,
			],
		]);
	}

	public function testBug3723(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3723.php'], []);
	}

	public function testBug6472(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6472.php'], []);
	}

	public function testFilterIteratorChildClass(): void
	{
		$this->analyse([__DIR__ . '/data/filter-iterator-child-class.php'], []);
	}

	public function testBug7662(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7662.php'], [
			[
				'Method Bug7662\Foo::__construct() has parameter $bar with no value type specified in iterable type array.',
				6,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
		]);
	}

}
