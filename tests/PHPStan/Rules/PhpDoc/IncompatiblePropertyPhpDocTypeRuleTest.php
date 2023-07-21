<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatiblePropertyPhpDocTypeRule>
 */
class IncompatiblePropertyPhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatiblePropertyPhpDocTypeRule(
			new GenericObjectTypeCheck(),
			new UnresolvableTypeHelper(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-property-phpdoc.php'], [
			[
				'PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$bar contains unresolvable type.',
				12,
			],
			[
				'PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$classStringInt contains unresolvable type.',
				18,
			],
			[
				'PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$fooGeneric contains generic type InvalidPhpDocDefinitions\Foo<stdClass> but class InvalidPhpDocDefinitions\Foo is not generic.',
				24,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$notEnoughTypesGenericfoo does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				30,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$tooManyTypesGenericfoo specifies 3 template types, but class InvalidPhpDocDefinitions\FooGeneric supports only 2: T, U',
				33,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$invalidTypeGenericfoo is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				36,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$anotherInvalidTypeGenericfoo is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				39,
			],
			[
				'PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$unknownClassConstant contains unresolvable type.',
				42,
			],
			[
				'PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$unknownClassConstant2 contains unresolvable type.',
				45,
			],
			[
				'Type projection covariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$genericRedundantTypeProjection is redundant, template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric has the same variance.',
				51,
				'You can safely remove the type projection.',
			],
			[
				'Type projection contravariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int> in PHPDoc tag @var for property InvalidPhpDoc\FooWithProperty::$genericIncompatibleTypeProjection is conflicting with variance of template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric.',
				57,
			],
		]);
	}

	public function testNativeTypes(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-property-native-types.php'], [
			[
				'PHPDoc tag @var for property IncompatiblePhpDocPropertyNativeType\Foo::$selfTwo with type object is not subtype of native type IncompatiblePhpDocPropertyNativeType\Foo.',
				12,
			],
			[
				'PHPDoc tag @var for property IncompatiblePhpDocPropertyNativeType\Foo::$foo with type IncompatiblePhpDocPropertyNativeType\Bar is incompatible with native type IncompatiblePhpDocPropertyNativeType\Foo.',
				15,
			],
			[
				'PHPDoc tag @var for property IncompatiblePhpDocPropertyNativeType\Foo::$stringOrInt with type int|string is not subtype of native type string.',
				21,
			],
			[
				'PHPDoc tag @var for property IncompatiblePhpDocPropertyNativeType\Lorem::$string with type T is not subtype of native type string.',
				45,
				'Write @template T of string to fix this.',
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-property-promoted.php'], [
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$bar contains unresolvable type.',
				16,
			],
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$classStringInt contains unresolvable type.',
				22,
			],
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$fooGeneric contains generic type InvalidPhpDocDefinitions\Foo<stdClass> but class InvalidPhpDocDefinitions\Foo is not generic.',
				28,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$notEnoughTypesGenericfoo does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				34,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int, InvalidArgumentException, string> in PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$tooManyTypesGenericfoo specifies 3 template types, but class InvalidPhpDocDefinitions\FooGeneric supports only 2: T, U',
				37,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$invalidTypeGenericfoo is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				40,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$anotherInvalidTypeGenericfoo is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				43,
			],
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$unknownClassConstant contains unresolvable type.',
				46,
			],
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\FooWithProperty::$unknownClassConstant2 contains unresolvable type.',
				49,
			],
			[
				'PHPDoc type for property InvalidPhpDocPromotedProperties\BazWithProperty::$bar with type string is incompatible with native type int.',
				61,
			],
		]);
	}

	public function testBug4227(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4227.php'], []);
	}

	public function testBug7240(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7240.php'], []);
	}

}
