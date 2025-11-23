<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Generics\TemplateTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<IncompatiblePhpDocTypeRule>
 */
class IncompatiblePhpDocTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new IncompatiblePhpDocTypeRule(
			$container->getByType(FileTypeMapper::class),
			new IncompatiblePhpDocTypeCheck(
				new GenericObjectTypeCheck(),
				new UnresolvableTypeHelper(),
				new GenericCallableRuleHelper(
					new TemplateTypeCheck(
						$reflectionProvider,
						new ClassNameCheck(
							new ClassCaseSensitivityCheck($reflectionProvider, true),
							new ClassForbiddenNameCheck($container),
							$reflectionProvider,
							$container,
						),
						new GenericObjectTypeCheck(),
						$typeAliasResolver,
						true,
					),
				),
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-types.php'], [
			[
				'PHPDoc tag @param references unknown parameter: $unknown',
				12,
			],
			[
				'PHPDoc tag @param for parameter $b with type array is incompatible with native type string.',
				12,
			],
			[
				'PHPDoc tag @param for parameter $d with type float|int is not subtype of native type int.',
				12,
			],
			[
				'PHPDoc tag @return with type string is incompatible with native type int.',
				66,
			],
			[
				'PHPDoc tag @return with type int|string is not subtype of native type int.',
				75,
			],
			[
				'PHPDoc tag @param for parameter $strings with type array<int> is incompatible with native type string.',
				91,
			],
			[
				'PHPDoc tag @param for parameter $numbers with type string is incompatible with native type int.',
				99,
			],
			[
				'PHPDoc tag @param for parameter $arr contains unresolvable type.',
				117,
			],
			[
				'PHPDoc tag @param references unknown parameter: $arrX',
				117,
			],
			[
				'PHPDoc tag @param for parameter $foo contains unresolvable type.',
				126,
			],
			[
				'PHPDoc tag @return contains unresolvable type.',
				126,
			],
			[
				'PHPDoc tag @param for parameter $a with type T is not subtype of native type int.',
				154,
				'Write @template T of int to fix this.',
			],
			[
				'PHPDoc tag @param for parameter $b with type U of DateTimeInterface is not subtype of native type DateTime.',
				154,
				'Write @template U of DateTime to fix this.',
			],
			[
				'PHPDoc tag @return with type U of DateTimeInterface is not subtype of native type DateTime.',
				154,
				'Write @template U of DateTime to fix this.',
			],
			[
				'PHPDoc tag @param for parameter $foo contains generic type InvalidPhpDocDefinitions\Foo<stdClass> but class InvalidPhpDocDefinitions\Foo is not generic.',
				185,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc tag @param for parameter $baz does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				185,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @param for parameter $lorem specifies 3 template types, but class InvalidPhpDocDefinitions\FooGeneric supports only 2: T, U',
				185,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc tag @param for parameter $ipsum is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				185,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @param for parameter $dolor is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				185,
			],
			[
				'PHPDoc tag @return contains generic type InvalidPhpDocDefinitions\Foo<stdClass> but class InvalidPhpDocDefinitions\Foo is not generic.',
				185,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc tag @return does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				201,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @return specifies 3 template types, but class InvalidPhpDocDefinitions\FooGeneric supports only 2: T, U',
				209,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc tag @return is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				217,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @return is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				225,
			],
			[
				'Type mixed in generic type InvalidPhpDocDefinitions\FooGeneric<int, mixed> in PHPDoc tag @param for parameter $t is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				242,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc tag @param for parameter $v is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				242,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @param for parameter $x is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				242,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @return is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				250,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc tag @return does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				266,
			],
			[
				'PHPDoc tag @return contains generic type InvalidPhpDocDefinitions\Foo<int, Exception> but class InvalidPhpDocDefinitions\Foo is not generic.',
				274,
			],
			[
				'PHPDoc tag @param for parameter $i with type TFoo is not subtype of native type int.',
				283,
				'Write @template TFoo of int to fix this.',
			],
			[
				'Call-site variance of covariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int> in PHPDoc tag @param for parameter $foo is redundant, template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric has the same variance.',
				301,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of covariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int> in PHPDoc tag @return is redundant, template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric has the same variance.',
				301,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of contravariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int> in PHPDoc tag @param for parameter $foo is in conflict with covariant template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric.',
				319,
			],
			[
				'Call-site variance of contravariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int> in PHPDoc tag @return is in conflict with covariant template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric.',
				319,
			],
			[
				'PHPDoc tag @param for parameter $cb contains unresolvable type.',
				328,
			],
			[
				'PHPDoc tag @param for parameter $cl contains unresolvable type.',
				328,
			],
		]);
	}

	public function testBug4643(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4643.php'], []);
	}

	public function testBug3753(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3753.php'], [
			[
				'PHPDoc tag @param for parameter $foo contains unresolvable type.',
				20,
			],
		]);
	}

	public function testTemplateTypeNativeTypeObject(): void
	{
		$this->analyse([__DIR__ . '/data/template-type-native-type-object.php'], [
			[
				'PHPDoc tag @return with type T is not subtype of native type object.',
				23,
				'Write @template T of object to fix this.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/generic-enum-param.php'], [
			[
				'PHPDoc tag @param for parameter $e contains generic type GenericEnumParam\FooEnum<int> but enum GenericEnumParam\FooEnum is not generic.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testValueOfEnum(): void
	{
		$this->analyse([__DIR__ . '/data/value-of-enum.php'], [
			[
				'PHPDoc tag @param for parameter $shouldError with type string is incompatible with native type int.',
				29,
			],
			[
				'PHPDoc tag @param for parameter $shouldError with type int is incompatible with native type string.',
				36,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testConditionalReturnType(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-conditional-return-type.php'], [
			[
				'PHPDoc tag @return with type ($p is int ? int : string) is not subtype of native type int.',
				25,
			],
		]);
	}

	public function testParamOut(): void
	{
		$this->analyse([__DIR__ . '/data/param-out.php'], [
			[
				'PHPDoc tag @param-out references unknown parameter: $z',
				23,
			],
			[
				'Parameter $i for PHPDoc tag @param-out is not passed by reference.',
				37,
			],
			[
				'PHPDoc tag @param-out for parameter $i contains generic type Exception<int, float> but class Exception is not generic.',
				51,
			],
			[
				'Generic type ParamOutPhpDocRule\FooBar<mixed> in PHPDoc tag @param-out for parameter $i does not specify all template types of class ParamOutPhpDocRule\FooBar: T, TT',
				58,
			],
			[
				'Type mixed in generic type ParamOutPhpDocRule\FooBar<mixed> in PHPDoc tag @param-out for parameter $i is not subtype of template type T of int of class ParamOutPhpDocRule\FooBar.',
				58,
			],
			[
				'Generic type ParamOutPhpDocRule\FooBar<int> in PHPDoc tag @param-out for parameter $i does not specify all template types of class ParamOutPhpDocRule\FooBar: T, TT',
				65,
			],

		]);
	}

	public function testBug10097(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10097.php'], []);
	}

	public function testGenericCallables(): void
	{
		$this->analyse([__DIR__ . '/data/generic-callables-incompatible.php'], [
			[
				'PHPDoc tag @param for parameter $existingClass template of Closure<stdClass of mixed>(stdClass): stdClass cannot have existing class stdClass as its name.',
				11,
			],
			[
				'PHPDoc tag @param for parameter $existingTypeAlias template of Closure<TypeAlias of mixed>(TypeAlias): TypeAlias cannot have existing type alias TypeAlias as its name.',
				18,
			],
			[
				'PHPDoc tag @param for parameter $invalidBoundType template T of Closure<T of GenericCallablesIncompatible\Invalid>(T): T has invalid bound type GenericCallablesIncompatible\Invalid.',
				25,
			],
			[
				'PHPDoc tag @param for parameter $shadows template T of Closure<T of mixed>(T): T shadows @template T for function GenericCallablesIncompatible\testShadowFunction.',
				40,
			],
			[
				'PHPDoc tag @param-out for parameter $existingClass template of Closure<stdClass of mixed>(stdClass): stdClass cannot have existing class stdClass as its name.',
				47,
			],
			[
				'PHPDoc tag @param for parameter $shadows template T of Closure<T of mixed, U of mixed>(T): T shadows @template T for method GenericCallablesIncompatible\Test::testShadowMethod.',
				60,
			],
			[
				'PHPDoc tag @param for parameter $shadows template U of Closure<T of mixed, U of mixed>(T): T shadows @template U for class GenericCallablesIncompatible\Test.',
				60,
			],
			[
				'PHPDoc tag @return template T of Closure<T of mixed, U of mixed>(T): T shadows @template T for method GenericCallablesIncompatible\Test::testShadowMethodReturn.',
				68,
			],
			[
				'PHPDoc tag @return template U of Closure<T of mixed, U of mixed>(T): T shadows @template U for class GenericCallablesIncompatible\Test.',
				68,
			],
			[
				'PHPDoc tag @return template of Closure<stdClass of mixed>(stdClass): stdClass cannot have existing class stdClass as its name.',
				76,
			],
			[
				'PHPDoc tag @return template of Closure<TypeAlias of mixed>(TypeAlias): TypeAlias cannot have existing type alias TypeAlias as its name.',
				83,
			],
			[
				'PHPDoc tag @return template T of Closure<T of GenericCallablesIncompatible\Invalid>(T): T has invalid bound type GenericCallablesIncompatible\Invalid.',
				90,
			],
			[
				'PHPDoc tag @return template T of Closure<T of mixed>(T): T shadows @template T for function GenericCallablesIncompatible\testShadowFunctionReturn.',
				105,
			],
			[
				'PHPDoc tag @param for parameter $existingClass template of Closure<stdClass of mixed>(stdClass): stdClass cannot have existing class stdClass as its name.',
				117,
			],
			[
				'PHPDoc tag @param for parameter $existingTypeAlias template of Closure<TypeAlias of mixed>(TypeAlias): TypeAlias cannot have existing type alias TypeAlias as its name.',
				124,
			],
			[
				'PHPDoc tag @param for parameter $invalidBoundType template T of Closure<T of GenericCallablesIncompatible\Invalid>(T): T has invalid bound type GenericCallablesIncompatible\Invalid.',
				131,
			],
			[
				'PHPDoc tag @return template of Closure<stdClass of mixed>(stdClass): stdClass cannot have existing class stdClass as its name.',
				145,
			],
			[
				'PHPDoc tag @return template of Closure<TypeAlias of mixed>(TypeAlias): TypeAlias cannot have existing type alias TypeAlias as its name.',
				152,
			],
			[
				'PHPDoc tag @return template T of Closure<T of GenericCallablesIncompatible\Invalid>(T): T has invalid bound type GenericCallablesIncompatible\Invalid.',
				159,
			],
			[
				'PHPDoc tag @param-out for parameter $existingClass template T of Closure<T of mixed>(T): T shadows @template T for function GenericCallablesIncompatible\shadowsParamOut.',
				175,
			],
			[
				'PHPDoc tag @param-out for parameter $existingClasses template T of Closure<T of mixed>(T): T shadows @template T for function GenericCallablesIncompatible\shadowsParamOutArray.',
				183,
			],
			[
				'PHPDoc tag @return template T of Closure<T of mixed>(T): T shadows @template T for function GenericCallablesIncompatible\shadowsReturnArray.',
				191,
			],
			[
				'PHPDoc tag @param for parameter $shadows template T of Closure<T of mixed>(T): T shadows @template T for class GenericCallablesIncompatible\Test3.',
				203,
			],
		]);
	}

	public function testBug10622(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10622.php'], []);
	}

	public function testBug10622B(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10622b.php'], []);
	}

	public function testParamClosureThis(): void
	{
		$this->analyse([__DIR__ . '/data/param-closure-this.php'], [
			[
				'PHPDoc tag @param-closure-this references unknown parameter: $b',
				20,
			],
			[
				'PHPDoc tag @param-closure-this for parameter $i contains unresolvable type.',
				27,
			],
			[
				'PHPDoc tag @param-closure-this for parameter $i contains unresolvable type.',
				34,
			],
			[
				'PHPDoc tag @param-closure-this is for parameter $i with non-Closure type string.',
				41,
			],
			[
				'PHPDoc tag @param-closure-this for parameter $i contains generic type Exception<int, float> but class Exception is not generic.',
				48,
			],
			[
				'Generic type ParamClosureThisPhpDocRule\FooBar<mixed> in PHPDoc tag @param-closure-this for parameter $i does not specify all template types of class ParamClosureThisPhpDocRule\FooBar: T, TT',
				55,
			],
			[
				'Type mixed in generic type ParamClosureThisPhpDocRule\FooBar<mixed> in PHPDoc tag @param-closure-this for parameter $i is not subtype of template type T of int of class ParamClosureThisPhpDocRule\FooBar.',
				55,
			],
			[
				'Generic type ParamClosureThisPhpDocRule\FooBar<int> in PHPDoc tag @param-closure-this for parameter $i does not specify all template types of class ParamClosureThisPhpDocRule\FooBar: T, TT',
				62,
			],
		]);
	}

	public function testGenericStatic(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-phpdoc-generic-static.php'], [
			[
				'Generic type static(IncompatiblePhpDocGenericStatic\Foo<int, string>) in PHPDoc tag @return specifies 2 template types, but class IncompatiblePhpDocGenericStatic\Foo supports only 1: T',
				14,
			],
		]);
	}

	public function testBug13452(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13452.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug13652(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13652.php'], [
			[
				'PHPDoc tag @return with type array{a: string, b: bool, c: int, d: float}[TKey] is not subtype of native type bool|int|string.',
				28,
			],
			[
				'PHPDoc tag @param for parameter $value with type array{a: string, b: bool, c: int, d: float}[TKey] is not subtype of native type bool|int|string.',
				48,
			],
		]);
	}

}
