<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class CallMethodsRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	private bool $checkNullables;

	private bool $checkUnionTypes;

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, $this->checkNullables, $this->checkThisOnly, $this->checkUnionTypes, $this->checkExplicitMixed, $this->checkImplicitMixed, false);
		return new CallMethodsRule(
			new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
			new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true),
		);
	}

	public function testIsCallablePhp7(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$this->markTestSkipped('Test requires PHP 7.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([ __DIR__ . '/data/call-methods-is-callable.php'], []);
	}

	public function testIsCallablePhp8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([ __DIR__ . '/data/call-methods-is-callable.php'], [
			[
				'Parameter #1 $str of method TestMethodsIsCallable\CheckIsCallable::test() expects callable(): mixed, \'Test…\' given.',
				10,
			],
		]);
	}

	public function testCallMethods(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([ __DIR__ . '/data/call-methods.php'], [
			[
				'Call to an undefined method Test\Foo::protectedMethodFromChild().',
				10,
			],
			[
				'Call to an undefined method Test\Bar::loremipsum().',
				40,
			],
			[
				'Call to private method foo() of class Test\Foo.',
				41,
			],
			[
				'Method Test\Foo::foo() invoked with 1 parameter, 0 required.',
				41,
			],
			[
				'Method Test\Foo::test() invoked with 0 parameters, 1 required.',
				46,
			],
			[
				'Cannot call method method() on string.',
				49,
			],
			[
				'Call to method doFoo() on an unknown class Test\UnknownClass.',
				63,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				66,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				68,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				70,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				72,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				75,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				76,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				77,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				78,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				79,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				81,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				83,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				84,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				85,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				86,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				90,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				91,
			],
			[
				'Call to an undefined method ArrayObject<int, stdClass>::doFoo().',
				108,
			],
			[
				'Method PDO::query() invoked with 0 parameters, 1-4 required.',
				113,
			],
			[
				'Parameter #1 $bar of method Test\ClassWithNullableProperty::doBar() is passed by reference, so it expects variables only.',
				167,
			],
			[
				'Parameter #1 $bar of method Test\ClassWithNullableProperty::doBar() is passed by reference, so it expects variables only.',
				168,
			],
			[
				'Cannot call method ipsum() on Test\Foo|null.',
				183,
			],
			[
				'Cannot call method ipsum() on Test\Bar|null.',
				184,
			],
			[
				'Cannot call method ipsum() on Test\Foo|null.',
				201,
			],
			[
				'Cannot call method ipsum() on Test\Bar|null.',
				202,
			],
			[
				'Method DateTimeZone::getTransitions() invoked with 3 parameters, 0-2 required.',
				214,
			],
			[
				'Result of method Test\ReturningSomethingFromConstructor::__construct() (void) is used.',
				234,
			],
			[
				'Cannot call method foo() on int|string.',
				254,
			],
			[
				'Method Test\FirstInterface::firstMethod() invoked with 1 parameter, 0 required.',
				281,
			],
			[
				'Method Test\SecondInterface::secondMethod() invoked with 1 parameter, 0 required.',
				282,
			],
			[
				'Cannot call method foo() on null.',
				299,
			],
			[
				'Call to method test() on an unknown class Test\FirstUnknownClass.',
				312,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to method test() on an unknown class Test\SecondUnknownClass.',
				312,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Cannot call method ipsum() on Test\Foo|null.',
				325,
			],
			[
				'Call to an undefined method Test\WithFooAndBarMethod|Test\WithFooMethod::bar().',
				355,
			],
			[
				'Call to an undefined method Test\SomeInterface&Test\WithFooMethod::bar().',
				372,
			],
			[
				'Method Test\Foo::test() invoked with 0 parameters, 1 required.',
				373,
			],
			[
				'Parameter #1 $foo of method Test\ObjectTypehint::doBar() expects Test\Foo, object given.',
				385,
			],
			[
				'Cannot call method test() on array<Test\UnknownClass>.',
				399,
			],
			[
				'Method Test\Foo::ipsum() invoked with 1 parameter, 0 required.',
				409,
			],
			[
				'Parameter #1 $test of method Test\NullableInPhpDoc::doFoo() expects string, null given.',
				427,
			],
			[
				'Parameter #1 $globalTitle of method Test\ThreeTypesCall::threeTypes() expects string, float given.',
				446,
			],
			[
				'Cannot call method find() on Test\NullCoalesce|null.',
				516,
			],
			[
				'Cannot call method find() on Test\NullCoalesce|null.',
				518,
			],
			[
				'Cannot call method find() on Test\NullCoalesce|null.',
				522,
			],
			[
				'Cannot call method find() on Test\NullCoalesce|null.',
				524,
			],
			[
				'Cannot call method find() on Test\NullCoalesce|null.',
				524,
			],
			[
				'Parameter #1 $param of method Test\IncompatiblePhpDocNullableTypeIssue::doFoo() expects string|null, int given.',
				551,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, false given.',
				565,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, Test\Foo given.',
				567,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, false given.',
				568,
			],
			[
				'Parameter #1 $s of method Test\ForeachSituation::takesInt() expects int|null, string|null given.',
				595,
			],
			[
				'Parameter #1 $str of method Test\LiteralArrayTypeCheck::test() expects string, int given.',
				632,
			],
			[
				'Parameter #1 $str of method Test\LiteralArrayTypeCheck::test() expects string, true given.',
				633,
			],
			[
				'Cannot call method add() on null.',
				647,
			],
			[
				'Parameter #1 $str of method Test\CheckIsCallable::test() expects callable(): mixed, \'nonexistentFunction\' given.',
				658,
			],
			[
				'Parameter #1 $str of method Test\CheckIsCallable::test() expects callable(): mixed, \'Test…\' given.',
				660,
			],
			[
				'Method Test\VariadicAnnotationMethod::definedInPhpDoc() invoked with 0 parameters, at least 1 required.',
				714,
			],
			[
				'Parameter #2 $str of method Test\PreIncString::doFoo() expects string, int given.',
				725,
			],
			[
				'Cannot call method bar() on string.',
				747,
			],
			[
				'Cannot call method bar() on string.',
				748,
			],
			[
				'Parameter #1 $std of method Test\CheckDefaultArrayKeys::doAmet() expects stdClass, (int|string) given.',
				791,
			],
			[
				'Parameter #1 $i of method Test\CheckDefaultArrayKeys::doBar() expects int, int|stdClass|string given.',
				797,
			],
			[
				'Parameter #1 $str of method Test\CheckDefaultArrayKeys::doBaz() expects string, int|stdClass|string given.',
				798,
			],
			[
				'Parameter #1 $intOrString of method Test\CheckDefaultArrayKeys::doLorem() expects int|string, int|stdClass|string given.',
				799,
			],
			[
				'Parameter #1 $stdOrInt of method Test\CheckDefaultArrayKeys::doIpsum() expects int|stdClass, int|stdClass|string given.', // should not expect this
				800,
			],
			[
				'Parameter #1 $stdOrString of method Test\CheckDefaultArrayKeys::doDolor() expects stdClass|string, int|stdClass|string given.', // should not expect this
				801,
			],
			[
				'Parameter #1 $dateOrString of method Test\CheckDefaultArrayKeys::doSit() expects DateTimeImmutable|string, int|stdClass|string given.',
				802,
			],
			[
				'Parameter #1 $std of method Test\CheckDefaultArrayKeys::doAmet() expects stdClass, int|stdClass|string given.',
				803,
			],
			[
				'Parameter #1 $i of method Test\CheckDefaultArrayKeys::doBar() expects int, int|string given.',
				866,
			],
			[
				'Parameter #1 $str of method Test\CheckDefaultArrayKeys::doBaz() expects string, int|string given.',
				867,
			],
			[
				'Cannot call method test() on string.',
				885,
			],
			[
				'Method Test\Foo::test() invoked with 0 parameters, 1 required.',
				886,
			],
			[
				'Call to an undefined method ReflectionType::getName().',
				896,
			],
			[
				'Call to an undefined method ReflectionType::getName().',
				897,
			],
			[
				'Call to an undefined method Test\Foo::lorem().',
				907,
			],
			[
				'Call to an undefined method Test\Foo::lorem().',
				911,
			],
			[
				'Cannot call method foo() on class-string|object.',
				914,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{class-string|object, \'foo\'} given.',
				915,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{class-string|object, \'bar\'} given.',
				916,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{object, \'bar\'} given.',
				921,
			],
			[
				'Parameter #1 $namespaceOrPrefix of method SimpleXMLElement::children() expects string|null, int given.',
				942,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int<0, max> given.',
				964,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int<1, max> given.',
				987,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int given.',
				1005,
			],
			[
				'Call to an undefined method Test\CallAfterPropertyEmpty::doBar().',
				1072,
			],
			[
				'Call to an undefined method Test\ArraySliceWithNonEmptyArray::doesNotExist().',
				1092,
			],
			[
				'Call to an undefined method Test\AssertInFor::doBar().',
				1207,
			],
			[
				'Parameter #1 $i of method Test\SubtractedMixed::requireInt() expects int, mixed given.',
				1277,
				'Type int has already been eliminated from mixed.',
			],
			[
				'Parameter #1 $i of method Test\SubtractedMixed::requireInt() expects int, mixed given.',
				1284,
				'Type int|string has already been eliminated from mixed.',
			],
			[
				'Parameter #1 $parameter of method Test\SubtractedMixed::requireIntOrString() expects int|string, mixed given.',
				1285,
				'Type int|string has already been eliminated from mixed.',
			],
			[
				'Parameter #2 $b of method Test\ExpectsExceptionGenerics::expectsExceptionUpperBound() expects Exception, Throwable given.',
				1378,
			],
			[
				'Parameter #1 $foo of method Test\ExpectsExceptionGenerics::requiresFoo() expects Test\Foo, Exception given.',
				1379,
			],
			[
				'Only iterables can be unpacked, array<int>|null given in argument #5.',
				1459,
			],
			[
				'Only iterables can be unpacked, int given in argument #6.',
				1460,
			],
			[
				'Only iterables can be unpacked, string given in argument #7.',
				1461,
			],
			[
				'Parameter #1 $s of method Test\ClassStringWithUpperBounds::doFoo() expects class-string<Exception>, string given.',
				1490,
			],
			[
				'Parameter #2 $object of method Test\ClassStringWithUpperBounds::doFoo() expects Exception, Throwable given.',
				1490,
			],
			[
				'Unable to resolve the template type T in call to method Test\ClassStringWithUpperBounds::doFoo()',
				1490,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Parameter #1 $other of method Test\CollectionWithStaticParam::add() expects static(Test\AppleCollection), Test\AppleCollection given.',
				1512,
			],
			[
				'Parameter #1 $a of method Test\\CallableWithMixedArray::doBar() expects callable(array<string>): array<string>, Closure(array): (array{\'foo\'}|null) given.',
				1533,
			],
			[
				'Parameter #1 $members of method Test\\ParameterTypeCheckVerbosity::doBar() expects array<array{id: string, code: string}>, array<array{code: string}> given.',
				1589,
				"Array does not have offset 'id'.",
			],
			[
				'Parameter #1 $test of method Test\NumericStringParam::sayHello() expects numeric-string, 123 given.',
				1657,
			],
			[
				'Parameter #1 $test of method Test\NumericStringParam::sayHello() expects numeric-string, \'abc\' given.',
				1658,
			],
			[
				'Parameter #1 $date of method Test\HelloWorld3::sayHello() expects array<DateTime|DateTimeImmutable>|int, DateTimeInterface given.',
				1732,
			],
			[
				'Parameter #1 $a of method Test\InvalidReturnTypeUsingArrayTemplateTypeBound::bar() expects array<string>, array<int, int> given.',
				1751,
			],
			[
				'Unable to resolve the template type T in call to method Test\InvalidReturnTypeUsingArrayTemplateTypeBound::bar()',
				1751,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Parameter #1 $code of method Test\\KeyOfParam::foo() expects \'jfk\'|\'lga\', \'sfo\' given.',
				1777,
			],
			[
				'Parameter #1 $code of method Test\\ValueOfParam::foo() expects \'John F. Kennedy…\'|\'La Guardia Airport\', \'Newark Liberty…\' given.',
				1802,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, numeric-string given.',
				1844,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, \'0\' given.',
				1845,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, string given.',
				1846,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, non-empty-string given.',
				1847,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, literal-string given.',
				1848,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, int given.',
				1849,
			],
		]);
	}

	public function testCallMethodsOnThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([ __DIR__ . '/data/call-methods.php'], [
			[
				'Call to an undefined method Test\Foo::protectedMethodFromChild().',
				10,
			],
			[
				'Call to an undefined method Test\Bar::loremipsum().',
				40,
			],
			[
				'Call to private method foo() of class Test\Foo.',
				41,
			],
			[
				'Method Test\Foo::foo() invoked with 1 parameter, 0 required.',
				41,
			],
			[
				'Method Test\Foo::test() invoked with 0 parameters, 1 required.',
				46,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				66,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				68,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				70,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				72,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				75,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				76,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				77,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				78,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				79,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				81,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				83,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				84,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				85,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				86,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				90,
			],
			[
				'Result of method Test\Bar::returnsVoid() (void) is used.',
				91,
			],
			[
				'Parameter #1 $bar of method Test\ClassWithNullableProperty::doBar() is passed by reference, so it expects variables only.',
				167,
			],
			[
				'Parameter #1 $bar of method Test\ClassWithNullableProperty::doBar() is passed by reference, so it expects variables only.',
				168,
			],
			[
				'Parameter #1 $foo of method Test\ObjectTypehint::doBar() expects Test\Foo, object given.',
				385,
			],
			[
				'Parameter #1 $test of method Test\NullableInPhpDoc::doFoo() expects string, null given.',
				427,
			],
			[
				'Parameter #1 $globalTitle of method Test\ThreeTypesCall::threeTypes() expects string, float given.',
				446,
			],
			[
				'Parameter #1 $param of method Test\IncompatiblePhpDocNullableTypeIssue::doFoo() expects string|null, int given.',
				551,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, false given.',
				565,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, Test\Foo given.',
				567,
			],
			[
				'Parameter #1 $i of method Test\TernaryEvaluation::doBar() expects int, false given.',
				568,
			],
			[
				'Parameter #1 $s of method Test\ForeachSituation::takesInt() expects int|null, string|null given.',
				595,
			],
			[
				'Parameter #1 $str of method Test\LiteralArrayTypeCheck::test() expects string, int given.',
				632,
			],
			[
				'Parameter #1 $str of method Test\LiteralArrayTypeCheck::test() expects string, true given.',
				633,
			],
			[
				'Parameter #1 $str of method Test\CheckIsCallable::test() expects callable(): mixed, \'nonexistentFunction\' given.',
				658,
			],
			[
				'Parameter #1 $str of method Test\CheckIsCallable::test() expects callable(): mixed, \'Test…\' given.',
				660,
			],
			[
				'Method Test\VariadicAnnotationMethod::definedInPhpDoc() invoked with 0 parameters, at least 1 required.',
				714,
			],
			[
				'Parameter #2 $str of method Test\PreIncString::doFoo() expects string, int given.',
				725,
			],
			[
				'Parameter #1 $std of method Test\CheckDefaultArrayKeys::doAmet() expects stdClass, (int|string) given.',
				791,
			],
			[
				'Parameter #1 $i of method Test\CheckDefaultArrayKeys::doBar() expects int, int|stdClass|string given.',
				797,
			],
			[
				'Parameter #1 $str of method Test\CheckDefaultArrayKeys::doBaz() expects string, int|stdClass|string given.',
				798,
			],
			[
				'Parameter #1 $intOrString of method Test\CheckDefaultArrayKeys::doLorem() expects int|string, int|stdClass|string given.',
				799,
			],
			[
				'Parameter #1 $stdOrInt of method Test\CheckDefaultArrayKeys::doIpsum() expects int|stdClass, int|stdClass|string given.', // should not expect this
				800,
			],
			[
				'Parameter #1 $stdOrString of method Test\CheckDefaultArrayKeys::doDolor() expects stdClass|string, int|stdClass|string given.', // should not expect this
				801,
			],
			[
				'Parameter #1 $dateOrString of method Test\CheckDefaultArrayKeys::doSit() expects DateTimeImmutable|string, int|stdClass|string given.',
				802,
			],
			[
				'Parameter #1 $std of method Test\CheckDefaultArrayKeys::doAmet() expects stdClass, int|stdClass|string given.',
				803,
			],
			[
				'Parameter #1 $i of method Test\CheckDefaultArrayKeys::doBar() expects int, int|string given.',
				866,
			],
			[
				'Parameter #1 $str of method Test\CheckDefaultArrayKeys::doBaz() expects string, int|string given.',
				867,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{class-string|object, \'foo\'} given.',
				915,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{class-string|object, \'bar\'} given.',
				916,
			],
			[
				'Parameter #1 $callable of method Test\\MethodExists::doBar() expects callable(): mixed, array{object, \'bar\'} given.',
				921,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int<0, max> given.',
				964,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int<1, max> given.',
				987,
			],
			[
				'Parameter #1 $s of method Test\IssetCumulativeArray::doBar() expects string, int given.',
				1005,
			],
			[
				'Call to an undefined method Test\CallAfterPropertyEmpty::doBar().',
				1072,
			],
			[
				'Parameter #1 $i of method Test\SubtractedMixed::requireInt() expects int, mixed given.',
				1277,
				'Type int has already been eliminated from mixed.',
			],
			[
				'Parameter #1 $i of method Test\SubtractedMixed::requireInt() expects int, mixed given.',
				1284,
				'Type int|string has already been eliminated from mixed.',
			],
			[
				'Parameter #1 $parameter of method Test\SubtractedMixed::requireIntOrString() expects int|string, mixed given.',
				1285,
				'Type int|string has already been eliminated from mixed.',
			],
			[
				'Parameter #2 $b of method Test\ExpectsExceptionGenerics::expectsExceptionUpperBound() expects Exception, Throwable given.',
				1378,
			],
			[
				'Parameter #1 $foo of method Test\ExpectsExceptionGenerics::requiresFoo() expects Test\Foo, Exception given.',
				1379,
			],
			[
				'Parameter #1 $s of method Test\ClassStringWithUpperBounds::doFoo() expects class-string<Exception>, string given.',
				1490,
			],
			[
				'Parameter #2 $object of method Test\ClassStringWithUpperBounds::doFoo() expects Exception, Throwable given.',
				1490,
			],
			[
				'Unable to resolve the template type T in call to method Test\ClassStringWithUpperBounds::doFoo()',
				1490,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Parameter #1 $other of method Test\CollectionWithStaticParam::add() expects static(Test\AppleCollection), Test\AppleCollection given.',
				1512,
			],
			[
				'Parameter #1 $a of method Test\\CallableWithMixedArray::doBar() expects callable(array<string>): array<string>, Closure(array): (array{\'foo\'}|null) given.',
				1533,
			],
			[
				'Parameter #1 $members of method Test\\ParameterTypeCheckVerbosity::doBar() expects array<array{id: string, code: string}>, array<array{code: string}> given.',
				1589,
				"Array does not have offset 'id'.",
			],
			[
				'Parameter #1 $test of method Test\NumericStringParam::sayHello() expects numeric-string, 123 given.',
				1657,
			],
			[
				'Parameter #1 $test of method Test\NumericStringParam::sayHello() expects numeric-string, \'abc\' given.',
				1658,
			],
			[
				'Parameter #1 $date of method Test\HelloWorld3::sayHello() expects array<DateTime|DateTimeImmutable>|int, DateTimeInterface given.',
				1732,
			],
			[
				'Parameter #1 $a of method Test\InvalidReturnTypeUsingArrayTemplateTypeBound::bar() expects array<string>, array<int, int> given.',
				1751,
			],
			[
				'Unable to resolve the template type T in call to method Test\InvalidReturnTypeUsingArrayTemplateTypeBound::bar()',
				1751,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Parameter #1 $code of method Test\\KeyOfParam::foo() expects \'jfk\'|\'lga\', \'sfo\' given.',
				1777,
			],
			[
				'Parameter #1 $code of method Test\\ValueOfParam::foo() expects \'John F. Kennedy…\'|\'La Guardia Airport\', \'Newark Liberty…\' given.',
				1802,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, numeric-string given.',
				1844,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, \'0\' given.',
				1845,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, string given.',
				1846,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, non-empty-string given.',
				1847,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, literal-string given.',
				1848,
			],
			[
				'Parameter #1 $string of method Test\NonFalsyString::acceptsNonFalsyString() expects non-falsy-string, int given.',
				1849,
			],
		]);
	}

	public function testCallTraitMethods(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-trait-methods.php'], [
			[
				'Call to an undefined method CallTraitMethods\Baz::unexistentMethod().',
				26,
			],
		]);
	}

	public function testCallTraitOverridenMethods(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-trait-overridden-methods.php'], []);
	}

	public function testCallInterfaceMethods(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-interface-methods.php'], [
			[
				'Call to an undefined method InterfaceMethods\Baz::barMethod().',
				25,
			],
		]);
	}

	public function testClosureBind(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/closure-bind.php'], [
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				12,
			],
			[
				'Call to an undefined method CallClosureBind\Bar::barMethod().',
				16,
			],
			[
				'Call to private method privateMethod() of class CallClosureBind\Foo.',
				18,
			],
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				19,
			],
			[
				'Call to an undefined method CallClosureBind\Bar::barMethod().',
				23,
			],
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				28,
			],
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				33,
			],
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				38,
			],
			[
				'Call to an undefined method CallClosureBind\Foo::nonexistentMethod().',
				44,
			],
			[
				'Parameter #2 $newScope of method Closure::bindTo() expects \'static\'|class-string|object|null, \'CallClosureBind\\\Bar3\' given.',
				74,
			],
		]);
	}

	public function testArrowFunctionClosureBind(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/arrow-function-bind.php'], [
			[
				'Call to an undefined method CallArrowFunctionBind\Foo::nonexistentMethod().',
				27,
			],
			[
				'Call to an undefined method CallArrowFunctionBind\Bar::barMethod().',
				29,
			],
			[
				'Call to an undefined method CallArrowFunctionBind\Foo::nonexistentMethod().',
				31,
			],
			[
				'Call to an undefined method CallArrowFunctionBind\Foo::nonexistentMethod().',
				33,
			],
			[
				'Call to an undefined method CallArrowFunctionBind\Foo::nonexistentMethod().',
				35,
			],
		]);
	}

	public function testCallVariadicMethods(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-variadic-methods.php'], [
			[
				'Method CallVariadicMethods\Foo::baz() invoked with 0 parameters, at least 1 required.',
				10,
			],
			[
				'Method CallVariadicMethods\Foo::lorem() invoked with 0 parameters, at least 2 required.',
				11,
			],
			[
				'Parameter #2 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				32,
			],
			[
				'Parameter #3 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				32,
			],
			[
				'Parameter #1 $int of method CallVariadicMethods\Foo::doVariadicString() expects int, string given.',
				34,
			],
			[
				'Parameter #3 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				42,
			],
			[
				'Parameter #4 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				42,
			],
			[
				'Parameter #5 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				42,
			],
			[
				'Parameter #6 ...$strings of method CallVariadicMethods\Foo::doVariadicString() expects string, int given.',
				42,
			],
			[
				'Method CallVariadicMethods\Foo::doIntegerParameters() invoked with 3 parameters, 2 required.',
				43,
			],
			[
				'Parameter #1 $foo of method CallVariadicMethods\Foo::doIntegerParameters() expects int, string given.',
				43,
			],
			[
				'Parameter #2 $bar of method CallVariadicMethods\Foo::doIntegerParameters() expects int, string given.',
				43,
			],
			[
				'Method CallVariadicMethods\Foo::doIntegerParameters() invoked with 3 parameters, 2 required.',
				44,
			],
			[
				'Parameter #1 ...$strings of method CallVariadicMethods\Bar::variadicStrings() expects string, int given.',
				85,
			],
			[
				'Parameter #2 ...$strings of method CallVariadicMethods\Bar::variadicStrings() expects string, int given.',
				85,
			],
			[
				'Parameter #1 ...$strings of method CallVariadicMethods\Bar::anotherVariadicStrings() expects string, int given.',
				88,
			],
			[
				'Parameter #2 ...$strings of method CallVariadicMethods\Bar::anotherVariadicStrings() expects string, int given.',
				88,
			],
		]);
	}

	public function testCallToIncorrectCaseMethodName(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/incorrect-method-case.php'], [
			[
				'Call to method IncorrectMethodCase\Foo::fooBar() with incorrect case: foobar',
				10,
			],
		]);
	}

	public function testNullableParameters(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/nullable-parameters.php'], [
			[
				'Method NullableParameters\Foo::doFoo() invoked with 0 parameters, 2 required.',
				6,
			],
			[
				'Method NullableParameters\Foo::doFoo() invoked with 1 parameter, 2 required.',
				7,
			],
			[
				'Method NullableParameters\Foo::doFoo() invoked with 3 parameters, 2 required.',
				10,
			],
		]);
	}

	public function testProtectedMethodCallFromParent(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/protected-method-call-from-parent.php'], []);
	}

	public function testSiblingMethodPrototype(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/sibling-method-prototype.php'], []);
	}

	public function testOverridenMethodPrototype(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/overriden-method-prototype.php'], []);
	}

	public function testCallMethodWithInheritDoc(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/calling-method-with-inheritdoc.php'], [
			[
				'Parameter #1 $i of method MethodWithInheritDoc\Baz::doFoo() expects int, string given.',
				65,
			],
			[
				'Parameter #1 $str of method MethodWithInheritDoc\Foo::doBar() expects string, int given.',
				67,
			],
		]);
	}

	public function testCallMethodWithInheritDocWithoutCurlyBraces(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/calling-method-with-inheritdoc-without-curly-braces.php'], [
			[
				'Parameter #1 $i of method MethodWithInheritDocWithoutCurlyBraces\Baz::doFoo() expects int, string given.',
				65,
			],
			[
				'Parameter #1 $str of method MethodWithInheritDocWithoutCurlyBraces\Foo::doBar() expects string, int given.',
				67,
			],
		]);
	}

	public function testCallMethodWithPhpDocsImplicitInheritance(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/calling-method-with-phpDocs-implicit-inheritance.php'], [
			[
				'Parameter #1 $i of method MethodWithPhpDocsImplicitInheritance\Baz::doFoo() expects int, string given.',
				56,
			],
			[
				'Parameter #1 $str of method MethodWithPhpDocsImplicitInheritance\Foo::doBar() expects string, int given.',
				58,
			],
			[
				'Parameter #1 $x of method MethodWithPhpDocsImplicitInheritance\Ipsum::doLorem() expects MethodWithPhpDocsImplicitInheritance\A, int given.',
				89,
			],
			[
				'Parameter #2 $y of method MethodWithPhpDocsImplicitInheritance\Ipsum::doLorem() expects MethodWithPhpDocsImplicitInheritance\B, int given.',
				89,
			],
			[
				'Parameter #3 $z of method MethodWithPhpDocsImplicitInheritance\Ipsum::doLorem() expects MethodWithPhpDocsImplicitInheritance\C, int given.',
				89,
			],
			[
				'Parameter #4 $d of method MethodWithPhpDocsImplicitInheritance\Ipsum::doLorem() expects MethodWithPhpDocsImplicitInheritance\D, int given.',
				89,
			],
			[
				'Parameter #1 $g of method MethodWithPhpDocsImplicitInheritance\Dolor::doLorem() expects MethodWithPhpDocsImplicitInheritance\A, int given.',
				104,
			],
			[
				'Parameter #2 $h of method MethodWithPhpDocsImplicitInheritance\Dolor::doLorem() expects MethodWithPhpDocsImplicitInheritance\B, int given.',
				104,
			],
			[
				'Parameter #3 $i of method MethodWithPhpDocsImplicitInheritance\Dolor::doLorem() expects MethodWithPhpDocsImplicitInheritance\C, int given.',
				104,
			],
			[
				'Parameter #4 $d of method MethodWithPhpDocsImplicitInheritance\Dolor::doLorem() expects MethodWithPhpDocsImplicitInheritance\D, int given.',
				104,
			],
			[
				'Parameter #1 $value of method ArrayObject<int,stdClass>::append() expects stdClass, Exception given.',
				115,
			],
			[
				'Parameter #1 $value of method ArrayObject<int,stdClass>::append() expects stdClass, Exception given.',
				129,
			],
			[
				'Parameter #1 $someValue of method MethodWithPhpDocsImplicitInheritance\TestArrayObject3::append() expects stdClass, Exception given.',
				146,
			],
		]);
	}

	public function testNegatedInstanceof(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/negated-instanceof.php'], []);
	}

	public function testInvokeMagicInvokeMethod(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/invoke-magic-method.php'], [
			[
				'Parameter #1 $foo of method InvokeMagicInvokeMethod\ClassForCallable::doFoo() expects callable(): mixed, InvokeMagicInvokeMethod\ClassForCallable given.',
				27,
			],
		]);
	}

	public function testCheckNullables(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/check-nullables.php'], [
			[
				'Parameter #1 $foo of method CheckNullables\Foo::doFoo() expects string, null given.',
				11,
			],
			[
				'Parameter #1 $foo of method CheckNullables\Foo::doFoo() expects string, string|null given.',
				15,
			],
		]);
	}

	public function testDoNotCheckNullables(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/check-nullables.php'], [
			[
				'Parameter #1 $foo of method CheckNullables\Foo::doFoo() expects string, null given.',
				11,
			],
		]);
	}

	public function testMysqliQuery(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/mysqli-query.php'], [
			[
				'Method mysqli::query() invoked with 0 parameters, 1-2 required.',
				4,
			],
		]);
	}

	public function testCallMethodsNullIssue(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/order.php'], []);
	}

	public function dataIterable(): array
	{
		return [
			[
				true,
			],
			[
				false,
			],
		];
	}

	/**
	 * @dataProvider dataIterable
	 */
	public function testIterables(bool $checkNullables): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = $checkNullables;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-methods-iterable.php'], [
			[
				'Parameter #1 $ids of method CallMethodsIterables\Uuid::bar() expects iterable<CallMethodsIterables\Uuid>, array<int, null> given.',
				14,
			],
			[
				'Parameter #1 $iterable of method CallMethodsIterables\Foo::acceptsSelfIterable() expects iterable<CallMethodsIterables\Foo>, iterable<CallMethodsIterables\Bar> given.',
				59,
			],
			[
				'Parameter #1 $iterable of method CallMethodsIterables\Foo::acceptsSelfIterable() expects iterable<CallMethodsIterables\Foo>, string given.',
				60,
			],
			[
				'Parameter #1 $iterableWithoutTypehint of method CallMethodsIterables\Foo::doFoo() expects iterable, int given.',
				62,
			],
			[
				'Parameter #2 $iterableWithIterableTypehint of method CallMethodsIterables\Foo::doFoo() expects iterable, int given.',
				62,
			],
			[
				'Parameter #3 $iterableWithConcreteTypehint of method CallMethodsIterables\Foo::doFoo() expects iterable<CallMethodsIterables\Bar>, int given.',
				62,
			],
			[
				'Parameter #4 $arrayWithIterableTypehint of method CallMethodsIterables\Foo::doFoo() expects array, int given.',
				62,
			],
			[
				'Parameter #5 $unionIterableType of method CallMethodsIterables\Foo::doFoo() expects CallMethodsIterables\Collection&iterable<CallMethodsIterables\Bar>, int given.',
				62,
			],
			[
				'Parameter #6 $mixedUnionIterableType of method CallMethodsIterables\Foo::doFoo() expects array, int given.',
				62,
			],
			[
				'Parameter #7 $unionIterableIterableType of method CallMethodsIterables\Foo::doFoo() expects CallMethodsIterables\Collection&iterable<CallMethodsIterables\Bar>, int given.',
				62,
			],
			[
				'Parameter #9 $integers of method CallMethodsIterables\Foo::doFoo() expects iterable<int>, int given.',
				62,
			],
			[
				'Parameter #10 $mixeds of method CallMethodsIterables\Foo::doFoo() expects iterable, int given.',
				62,
			],
		]);
	}

	public function testAcceptThrowable(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/accept-throwable.php'], [
			[
				'Parameter #1 $i of method AcceptThrowable\Foo::doBar() expects int, AcceptThrowable\SomeInterface&Throwable given.',
				41,
			],
			[
				'Parameter #1 $i of method AcceptThrowable\Foo::doBar() expects int, AcceptThrowable\InterfaceExtendingThrowable given.',
				44,
			],
			[
				'Parameter #1 $i of method AcceptThrowable\Foo::doBar() expects int, AcceptThrowable\NonExceptionClass&Throwable given.',
				47,
			],
			[
				'Parameter #1 $i of method AcceptThrowable\Foo::doBar() expects int, Exception given.',
				50,
			],
		]);
	}

	public function testWithoutCheckUnionTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = false;
		$this->analyse([__DIR__ . '/data/without-union-types.php'], [
			[
				'Method CallMethodsWithoutUnionTypes\Foo::doFoo() invoked with 3 parameters, 0 required.',
				14,
			],
		]);
	}

	public function testStrictTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/call-methods-strict.php'], [
			[
				'Parameter #1 $foo of method Test\ClassWithToString::acceptsString() expects string, Test\ClassWithToString given.',
				7,
			],
		]);
	}

	public function testAliasedTraitsProblem(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/aliased-traits-problem.php'], []);
	}

	public function testClosureCallInvocations(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/closure-call.php'], [
			[
				'Method Closure::call() invoked with 0 parameters, 2 required.',
				9,
			],
			[
				'Method Closure::call() invoked with 1 parameter, 2 required.',
				10,
			],
			[
				'Method Closure::call() invoked with 1 parameter, 2 required.',
				11,
			],
			[
				'Parameter #1 $newThis of method Closure::call() expects object, int given.',
				11,
			],
			[
				'Parameter #2 $thing of method Closure::call() expects object, int given.',
				12,
			],
			[
				'Parameter #1 $newThis of method Closure::call() expects object, int given.',
				13,
			],
			[
				'Method Closure::call() invoked with 3 parameters, 2 required.',
				14,
			],
			[
				'Result of method Closure::call() (void) is used.',
				18,
			],
		]);
	}

	public function testMixin(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/mixin.php'], [
			[
				'Method MixinMethods\Foo::doFoo() invoked with 1 parameter, 0 required.',
				30,
			],
			[
				'Method MixinMethods\Foo::doFoo() invoked with 1 parameter, 0 required.',
				40,
			],
			[
				'Method Exception::getMessage() invoked with 1 parameter, 0 required.',
				61,
			],
			[
				'Call to an undefined method MixinMethods\GenericFoo<Exception>::getMessagee().',
				62,
			],
		]);
	}

	public function testRecursiveIteratorIterator(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/recursive-iterator-iterator.php'], [
			[
				'Method RecursiveDirectoryIterator::getSubPathname() invoked with 1 parameter, 0 required.',
				14,
			],
		]);
	}

	public function testMergeInheritedPhpDocs(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/merge-inherited-param.php'], [
			[
				'Parameter #1 $uno of method CallMethodsPhpDocMergeParamInherited\ParentClass::method() expects CallMethodsPhpDocMergeParamInherited\A, CallMethodsPhpDocMergeParamInherited\D given.',
				37,
			],
			[
				'Parameter #2 $dos of method CallMethodsPhpDocMergeParamInherited\ParentClass::method() expects CallMethodsPhpDocMergeParamInherited\B, CallMethodsPhpDocMergeParamInherited\D given.',
				37,
			],
			[
				'Parameter #1 $one of method CallMethodsPhpDocMergeParamInherited\ChildClass::method() expects CallMethodsPhpDocMergeParamInherited\C, CallMethodsPhpDocMergeParamInherited\B given.',
				42,
			],
			[
				'Parameter #2 $two of method CallMethodsPhpDocMergeParamInherited\ChildClass::method() expects CallMethodsPhpDocMergeParamInherited\B, CallMethodsPhpDocMergeParamInherited\D given.',
				42,
			],
		]);
	}

	public function testShadowedTraitMethod(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/shadowed-trait-method.php'], []);
	}

	public function dataExplicitMixed(): array
	{
		return [
			[
				true,
				[
					[
						'Cannot call method foo() on mixed.',
						17,
					],
					[
						'Cannot call method foo() on T of mixed.',
						26,
					],
					[
						'Parameter #1 $i of method CheckExplicitMixedMethodCall\Bar::doBar() expects int, mixed given.',
						43,
					],
					[
						'Parameter #1 $i of method CheckExplicitMixedMethodCall\Bar::doBar() expects int, T given.',
						65,
					],
					[
						'Parameter #1 $cb of method CheckExplicitMixedMethodCall\CallableMixed::doFoo() expects callable(mixed): void, Closure(int): void given.',
						133,
						'Type int of parameter #1 $i of passed callable needs to be same or wider than parameter type mixed of accepting callable.',
					],
					[
						'Parameter #1 $cb of method CheckExplicitMixedMethodCall\CallableMixed::doBar2() expects callable(): int, Closure(): mixed given.',
						152,
					],
				],
			],
			[
				false,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataExplicitMixed
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testExplicitMixed(bool $checkExplicitMixed, array $errors): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->analyse([__DIR__ . '/data/check-explicit-mixed.php'], $errors);
	}

	public function dataImplicitMixed(): array
	{
		return [
			[
				true,
				[
					[
						'Cannot call method foo() on mixed.',
						16,
					],
					[
						'Parameter #1 $i of method CheckImplicitMixedMethodCall\Bar::doBar() expects int, mixed given.',
						42,
					],
					[
						'Parameter #1 $cb of method CheckImplicitMixedMethodCall\CallableMixed::doBar2() expects callable(): int, Closure(): mixed given.',
						139,
					],
				],
			],
			[
				false,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataImplicitMixed
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testImplicitMixed(bool $checkImplicitMixed, array $errors): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkImplicitMixed = $checkImplicitMixed;
		$this->analyse([__DIR__ . '/data/check-implicit-mixed.php'], $errors);
	}

	public function testBug3409(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3409.php'], []);
	}

	public function testBug2600(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-2600.php'], [
			[
				'Method Bug2600\Foo::doBar() invoked with 3 parameters, 0-1 required.',
				10,
			],
		]);
	}

	public function testBug3415(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3415.php'], []);
	}

	public function testBug3415Two(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3415-2.php'], []);
	}

	public function testBug3445(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3445.php'], [
			[
				'Parameter #1 $test of method Bug3445\Foo::doFoo() expects Bug3445\Foo, $this(Bug3445\Bar) given.',
				26,
			],
		]);
	}

	public function testBug3481(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3481.php'], [
			[
				'Method Bug3481\Foo::doSomething() invoked with 2 parameters, 3 required.',
				34,
			],
			[
				'Parameter #1 $a of method Bug3481\Foo::doSomething() expects string, int|string given.',
				44,
			],
		]);
	}

	public function testBug3683(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3683.php'], [
			[
				'Parameter #1 $exception of method Generator<mixed,mixed,mixed,mixed>::throw() expects Throwable, int given.',
				7,
			],
		]);
	}

	public function testStringable(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/stringable.php'], []);
	}

	public function testStringableStrictTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/stringable-strict.php'], [
			[
				'Parameter #1 $s of method TestStringables\Dolor::doFoo() expects string, TestStringables\Bar given.',
				15,
			],
		]);
	}

	public function testMatchExpressionVoidIsUsed(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/match-expr-void-used.php'], [
			[
				'Result of method MatchExprVoidUsed\Foo::doLorem() (void) is used.',
				10,
			],
			[
				'Result of method MatchExprVoidUsed\Foo::doBar() (void) is used.',
				11,
			],
		]);
	}

	public function testNullSafe(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/nullsafe-method-call.php'], [
			[
				'Method NullsafeMethodCall\Foo::doBar() invoked with 1 parameter, 0 required.',
				11,
			],
			[
				'Parameter #1 $passedByRef of method NullsafeMethodCall\Foo::doBaz() is passed by reference, so it expects variables only.',
				26,
			],
			[
				'Parameter #1 $passedByRef of method NullsafeMethodCall\Foo::doBaz() is passed by reference, so it expects variables only.',
				27,
			],
			[
				'Cannot call method foo() on null.',
				33,
			],
			[
				'Cannot call method foo() on null.',
				34,
			],
		]);
	}

	public function testDisallowNamedArguments(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$this->markTestSkipped('Test requires PHP earlier than 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/disallow-named-arguments.php'], [
			[
				'Named arguments are supported only on PHP 8.0 and later.',
				10,
			],
		]);
	}

	public function testDisallowNamedArgumentsInPhpVersionScope(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/disallow-named-arguments-php-version-scope.php'], [
			[
				'Named arguments are supported only on PHP 8.0 and later.',
				26,
			],
		]);
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/named-arguments.php'], [
			[
				'Named argument cannot be followed by a positional argument.',
				21,
			],
			[
				'Named argument cannot be followed by a positional argument.',
				22,
			],
			[
				'Missing parameter $j (int) in call to method NamedArgumentsMethod\Foo::doFoo().',
				19,
			],
			[
				'Missing parameter $k (int) in call to method NamedArgumentsMethod\Foo::doFoo().',
				19,
			],
			[
				'Argument for parameter $i has already been passed.',
				26,
			],
			[
				'Argument for parameter $i has already been passed.',
				32,
			],
			[
				'Missing parameter $k (int) in call to method NamedArgumentsMethod\Foo::doFoo().',
				37,
			],
			[
				'Unknown parameter $z in call to method NamedArgumentsMethod\Foo::doFoo().',
				46,
			],
			[
				'Parameter #1 $i of method NamedArgumentsMethod\Foo::doFoo() expects int, string given.',
				50,
			],
			[
				'Parameter $j of method NamedArgumentsMethod\Foo::doFoo() expects int, string given.',
				57,
			],
			[
				'Parameter $i of method NamedArgumentsMethod\Foo::doBaz() is passed by reference, so it expects variables only.',
				70,
			],
			[
				'Parameter $i of method NamedArgumentsMethod\Foo::doBaz() is passed by reference, so it expects variables only.',
				71,
			],
			[
				'Named argument cannot be followed by an unpacked (...) argument.',
				73,
			],
			[
				'Parameter $j of method NamedArgumentsMethod\Foo::doFoo() expects int, string given.',
				75,
			],
			[
				'Named argument cannot be followed by a positional argument.',
				77,
			],
			[
				'Missing parameter $j (int) in call to method NamedArgumentsMethod\Foo::doFoo().',
				77,
			],
			[
				'Parameter #3 ...$args of method NamedArgumentsMethod\Foo::doIpsum() expects string, int given.',
				87,
			],
			[
				'Parameter $b of method NamedArgumentsMethod\Foo::doIpsum() expects int, string given.',
				90,
			],
			[
				'Parameter $b of method NamedArgumentsMethod\Foo::doIpsum() expects int, string given.',
				91,
			],
			[
				'Named argument foo for variadic parameter ...$args of method NamedArgumentsMethod\Foo::doIpsum() expects string, int given.',
				91,
			],
			[
				'Missing parameter $b (int) in call to method NamedArgumentsMethod\Foo::doIpsum().',
				92,
			],
			[
				'Missing parameter $a (int) in call to method NamedArgumentsMethod\Foo::doIpsum().',
				93,
			],
			[
				'Unpacked argument (...) cannot be followed by a non-unpacked argument.',
				94,
			],
			[
				'Named argument foo for variadic parameter ...$args of method NamedArgumentsMethod\Foo::doIpsum() expects string, int given.',
				95,
			],
			[
				'Named argument bar for variadic parameter ...$args of method NamedArgumentsMethod\Foo::doIpsum() expects string, int given.',
				95,
			],
		]);
	}

	public function testBug4199(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/bug-4199.php'], [
			[
				'Cannot call method answer() on Bug4199\Baz|null.',
				37,
			],
		]);
	}

	public function testBug4188(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/bug-4188.php'], []);
	}

	public function testOnlyRelevantUnableToResolveTemplateType(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/only-relevant-unable-to-resolve-template-type.php'], [
			[
				'Parameter #1 $a of method OnlyRelevantUnableToResolve\Foo::doBaz() expects array<mixed>, int given.',
				41,
			],
			[
				'Unable to resolve the template type T in call to method OnlyRelevantUnableToResolve\Foo::doBaz()',
				41,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
		]);
	}

	public function testBug4552(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4552.php'], []);
	}

	public function testBug2837(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-2837.php'], []);
	}

	public function testBug2298(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-2298.php'], []);
	}

	public function testBug1661(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-1661.php'], []);
	}

	public function testBug1656(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-1656.php'], []);
	}

	public function testBug3534(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3534.php'], []);
	}

	public function testBug4557(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4557.php'], []);
	}

	public function testBug4209(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4209.php'], []);
	}

	public function testBug4209Two(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4209-2.php'], []);
	}

	public function testBug3321(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-3321.php'], []);
	}

	public function testBug4498(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4498.php'], []);
	}

	public function testBug3922(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-3922.php'], [
			[
				'Parameter #1 $query of method Bug3922\FooQueryHandler::handle() expects Bug3922\FooQuery, Bug3922\BarQuery given.',
				63,
			],
		]);
	}

	public function testBug4642(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4642.php'], []);
	}

	public function testBug4008(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4008.php'], []);
	}

	public function testBug3546(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3546.php'], []);
	}

	public function testBug4800(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/bug-4800.php'], [
			[
				'Missing parameter $bar (string) in call to method Bug4800\HelloWorld2::a().',
				36,
			],
		]);
	}

	public function testGenericReturnTypeResolvedToNever(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/generic-return-type-never.php'], [
			[
				'Return type of call to method GenericReturnTypeNever\Foo::doBar() contains unresolvable type.',
				70,
			],
			[
				'Return type of call to method GenericReturnTypeNever\Foo::doBazBaz() contains unresolvable type.',
				73,
			],
		]);
	}

	public function testUnableToResolveCallbackParameterType(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/unable-to-resolve-callback-parameter-type.php'], []);
	}

	public function testBug4083(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4083.php'], []);
	}

	public function testBug5253(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5253.php'], []);
	}

	public function testBug4844(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4844.php'], []);
	}

	public function testBug5258(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5258.php'], []);
	}

	public function testBug5591(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5591.php'], []);
	}

	public function testGenericObjectLowerBound(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/generic-object-lower-bound.php'], [
			[
				'Parameter #1 $c of method GenericObjectLowerBound\Foo::doFoo() expects GenericObjectLowerBound\Collection<GenericObjectLowerBound\Cat|GenericObjectLowerBound\Dog>, GenericObjectLowerBound\Collection<GenericObjectLowerBound\Dog> given.',
				48,
				'Template type T on class GenericObjectLowerBound\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
		]);
	}

	public function testNonEmptyStringVerbosity(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/non-empty-string-verbosity.php'], [
			[
				'Parameter #1 $i of method NonEmptyStringVerbosity\Foo::doBar() expects int, string given.',
				13,
			],
		]);
	}

	public function testBug5536(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5536.php'], []);
	}

	public function testBug5372(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5372.php'], [
			[
				'Parameter #1 $list of method Bug5372\Foo::takesStrings() expects Bug5372\Collection<int, string>, Bug5372\Collection<int, non-falsy-string> given.',
				64,
				'Template type T on class Bug5372\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Parameter #1 $list of method Bug5372\Foo::takesStrings() expects Bug5372\Collection<int, string>, Bug5372\Collection<int, class-string> given.',
				68,
				'Template type T on class Bug5372\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Parameter #1 $list of method Bug5372\Foo::takesStrings() expects Bug5372\Collection<int, string>, Bug5372\Collection<int, class-string> given.',
				72,
				'Template type T on class Bug5372\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Parameter #1 $list of method Bug5372\Foo::takesStrings() expects Bug5372\Collection<int, string>, Bug5372\Collection<int, literal-string> given.',
				81,
				'Template type T on class Bug5372\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Parameter #1 $list of method Bug5372\Foo::takesStrings() expects Bug5372\Collection<int, string>, Bug5372\Collection<int, literal-string> given.',
				85,
				'Template type T on class Bug5372\Collection is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
		]);
	}

	public function testLiteralString(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/literal-string.php'], [
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, string given.',
				18,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, int given.',
				21,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, 1 given.',
				22,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, mixed given.',
				25,
			],
			[
				'Parameter #1 $a of method LiteralStringMethod\Foo::requireArrayOfLiteralStrings() expects array<literal-string>, array<string> given.',
				58,
			],
			[
				'Parameter #1 $a of method LiteralStringMethod\Foo::requireArrayOfLiteralStrings() expects array<literal-string>, array<mixed> given.',
				60,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, array<mixed> given.',
				65,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, mixed given.',
				66,
			],
			[
				'Parameter #1 $s of method LiteralStringMethod\Foo::requireLiteralString() expects literal-string, mixed given.',
				67,
			],
		]);
	}

	public function testBug3555(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3555.php'], [
			[
				'Parameter #1 $arg of method Bug3555\Enum::run() expects 1|2|3|4|5|6|7|8|9, 100 given.',
				28,
			],
		]);
	}

	public function testBug3530(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3530.php'], []);
	}

	public function testBug5562(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5562.php'], []);
	}

	public function testBug4211(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-4211.php'], []);
	}

	public function testBug3514(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3514.php'], []);
	}

	public function testBug3465(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-3465.php'], []);
	}

	public function testBug5868(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5868.php'], [
			[
				'Cannot call method nullable1() on Bug5868\HelloWorld|null.',
				14,
			],
			[
				'Cannot call method nullable2() on Bug5868\HelloWorld|null.',
				15,
			],
			[
				'Cannot call method nullable3() on Bug5868\HelloWorld|null.',
				16,
			],
		]);
	}

	public function testBug5460(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-5460.php'], []);
	}

	public function testFirstClassCallable(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		// handled by a different rule
		$this->analyse([__DIR__ . '/data/first-class-method-callable.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/call-method-in-enum.php'], [
			[
				'Call to an undefined method CallMethodInEnum\Foo::doNonexistent().',
				11,
			],
			[
				'Call to an undefined method CallMethodInEnum\Bar::doNonexistent().',
				22,
			],
			[
				'Parameter #1 $countryName of method CallMethodInEnum\FooCall::hello() expects \'The Netherlands\'|\'United States\', CallMethodInEnum\CountryNo::NL given.',
				63,
			],
			[
				'Parameter #1 $countryMap of method CallMethodInEnum\FooCall::helloArray() expects array<\'The Netherlands\'|\'United States\', bool>, array{abc: true} given.',
				66,
			],
			[
				'Parameter #1 $countryMap of method CallMethodInEnum\FooCall::helloArray() expects array<\'The Netherlands\'|\'United States\', bool>, array{abc: 123} given.',
				67,
			],
			[
				'Parameter #1 $countryMap of method CallMethodInEnum\FooCall::helloArray() expects array<\'The Netherlands\'|\'United States\', bool>, array{true} given.',
				70,
			],
			[
				'Parameter #1 $one of method CallMethodInEnum\TestPassingEnums::requireOne() expects CallMethodInEnum\TestPassingEnums::ONE, $this(CallMethodInEnum\TestPassingEnums)&CallMethodInEnum\TestPassingEnums given.',
				91,
			],
			[
				'Parameter #1 $one of method CallMethodInEnum\TestPassingEnums::requireOne() expects CallMethodInEnum\TestPassingEnums::ONE, $this(CallMethodInEnum\TestPassingEnums)&CallMethodInEnum\TestPassingEnums given.',
				99,
			],
			[
				'Parameter #1 $one of method CallMethodInEnum\TestPassingEnums::requireOne() expects CallMethodInEnum\TestPassingEnums::ONE, $this(CallMethodInEnum\TestPassingEnums)&CallMethodInEnum\TestPassingEnums given.',
				106,
			],
		]);
	}

	public function testBug6239(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('This test needs PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-6293.php'], []);
	}

	public function testBug6306(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-6306.php'], []);
	}

	public function testRectorDoWhileVarIssue(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/rector-do-while-var-issue.php'], [
			[
				'Parameter #1 $cls of method RectorDoWhileVarIssue\Foo::processCharacterClass() expects string, int|string given.',
				24,
			],
		]);
	}

	public function testReadOnlyPropertyPassedByReference(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/readonly-property-passed-by-reference.php'], [
			[
				'Parameter #1 $param is passed by reference so it does not accept readonly property ReadonlyPropertyPassedByRef\Foo::$bar.',
				15,
			],
			[
				'Parameter $param is passed by reference so it does not accept readonly property ReadonlyPropertyPassedByRef\Foo::$bar.',
				16,
			],
		]);
	}

	public function testBug6055(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6055.php'], []);
	}

	public function testBug6081(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6081.php'], []);
	}

	public function testBug6236(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6236.php'], []);
	}

	public function testBug6118(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6118.php'], []);
	}

	public function testBug6464(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6464.php'], []);
	}

	public function testBug6423(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6423.php'], []);
	}

	public function testBug5869(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5869.php'], []);
	}

	public function testGenericsEmptyArray(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generics-empty-array.php'], []);
	}

	public function testGenericsInferCollection(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generics-infer-collection.php'], [
			[
				'Parameter #1 $c of method GenericsInferCollection\Foo::doBar() expects GenericsInferCollection\ArrayCollection<int, int>, GenericsInferCollection\ArrayCollection<int, string> given.',
				43,
			],
			[
				'Parameter #1 $c of method GenericsInferCollection\Bar::doBar() expects GenericsInferCollection\ArrayCollection2<int, int>, GenericsInferCollection\ArrayCollection2<(int|string), mixed> given.',
				62,
			],
			[
				'Parameter #1 $c of method GenericsInferCollection\Bar::doBar() expects GenericsInferCollection\ArrayCollection2<int, int>, GenericsInferCollection\ArrayCollection2<(int|string), mixed> given.',
				63,
			],
			[
				'Parameter #1 $c of method GenericsInferCollection\Bar::doBar() expects GenericsInferCollection\ArrayCollection2<int, int>, GenericsInferCollection\ArrayCollection2<(int|string), mixed> given.',
				64,
			],
		]);
	}

	public function testGenericsInferCollectionLevel8(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/generics-infer-collection.php'], [
			[
				'Parameter #1 $c of method GenericsInferCollection\Foo::doBar() expects GenericsInferCollection\ArrayCollection<int, int>, GenericsInferCollection\ArrayCollection<int, string> given.',
				43,
			],
		]);
	}

	public function testGenericVariance(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/generic-variance.php'], [
			[
				'Parameter #1 $param of method GenericVarianceCall\Foo::invariant() expects GenericVarianceCall\Invariant<GenericVarianceCall\B>, GenericVarianceCall\Invariant<GenericVarianceCall\A> given.',
				45,
			],
			[
				'Parameter #1 $param of method GenericVarianceCall\Foo::invariant() expects GenericVarianceCall\Invariant<GenericVarianceCall\B>, GenericVarianceCall\Invariant<GenericVarianceCall\C> given.',
				53,
				'Template type T on class GenericVarianceCall\Invariant is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Parameter #1 $param of method GenericVarianceCall\Foo::covariant() expects GenericVarianceCall\Covariant<GenericVarianceCall\B>, GenericVarianceCall\Covariant<GenericVarianceCall\A> given.',
				60,
			],
			[
				'Parameter #1 $param of method GenericVarianceCall\Foo::contravariant() expects GenericVarianceCall\Contravariant<GenericVarianceCall\B>, GenericVarianceCall\Contravariant<GenericVarianceCall\C> given.',
				83,
			],
			[
				'Parameter #1 $param of method GenericVarianceCall\Foo::invariantArray() expects array{GenericVarianceCall\Invariant<GenericVarianceCall\B>}, array{GenericVarianceCall\Invariant<GenericVarianceCall\C>} given.',
				97,
				'Offset 0 (GenericVarianceCall\Invariant<GenericVarianceCall\B>) does not accept type GenericVarianceCall\Invariant<GenericVarianceCall\C>: Template type T on class GenericVarianceCall\Invariant is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
		]);
	}

	public function testBug6904(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6904.php'], []);
	}

	public function testBug6917(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6917.php'], []);
	}

	public function testBug3284(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-3284.php'], []);
	}

	public function testUnresolvableParameter(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/unresolvable-parameter.php'], [
			[
				'Parameter #2 $v of method UnresolvableParameter\HelloWorld::foo() contains unresolvable type.',
				18,
			],
			[
				'Parameter #2 $v of method UnresolvableParameter\HelloWorld::foo() contains unresolvable type.',
				19,
			],
			[
				'Parameter #2 $v of method UnresolvableParameter\HelloWorld::foo() expects 1, 0 given.',
				21,
			],
		]);
	}

	public function testConditionalComplexTemplates(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/conditional-complex-templates.php'], []);
	}

	public function testBug6291(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6291.php'], []);
	}

	public function testBug1517(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-1517.php'], []);
	}

	public function testBug7593(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7593.php'], []);
	}

	public function testBug6946(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6946.php'], []);
	}

	public function testBug5754(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5754.php'], []);
	}

	public function testBug7600(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7600.php'], []);
	}

	public function testBug8058(): void
	{
		if (PHP_VERSION_ID < 80200) {
			$this->markTestSkipped('Test requires PHP 8.2');
		}
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-8058.php'], []);
	}

	public function testBug8058b(): void
	{
		if (PHP_VERSION_ID >= 80200) {
			$this->markTestSkipped('Test requires PHP before 8.2');
		}
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-8058.php'], [
			[
				'Call to an undefined method mysqli::execute_query().',
				11,
			],
		]);
	}

	public function testArrayCastListTypes(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/array-cast-list-types.php'], []);
	}

	public function testBug5623(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-5623.php'], []);
	}

	public function testImagickPixel(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/imagick-pixel.php'], []);
	}

	public function testNewInstanceArgsIssue8679(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/reflection-class-issue-8679.php'], []);
	}

	public function testNonEmptyArray(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;

		$this->analyse([__DIR__ . '/data/non-empty-array.php'], [
			[
				'Parameter #1 $nonEmpty of method AcceptNonEmptyArray\Foo::requireNonEmpty() expects non-empty-array<int>, array<int> given.',
				15,
				'array<int> might be empty.',
			],
			[
				'Parameter #1 $nonEmpty of method AcceptNonEmptyArray\Foo::requireNonEmpty() expects non-empty-array<int>, array{} given.',
				17,
				'array{} is empty.',
			],
		]);
	}

	public function testBug8752(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8752.php'], [
			[
				'Cannot call method abc() on class-string.',
				18,
			],
		]);
	}

	public function dataCallablesWithoutCheckNullables(): iterable
	{
		yield [false, false, []];
		yield [true, false, []];

		$errors = [
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBar() expects callable(float|null): (float|null), Closure(float): float given.',
				25,
				'Type float of parameter #1 $f of passed callable needs to be same or wider than parameter type float|null of accepting callable.',
			],
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBaz() expects Closure(float|null): (float|null), Closure(float): float given.',
				28,
				'Type float of parameter #1 $f of passed callable needs to be same or wider than parameter type float|null of accepting callable.',
			],
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBar2() expects callable(float|null): float, Closure(float|null): (float|null) given.',
				32,
			],
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBaz2() expects Closure(float|null): float, Closure(float|null): (float|null) given.',
				35,
			],
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBar2() expects callable(float|null): float, Closure(float): float given.',
				45,
				'Type float of parameter #1 $f of passed callable needs to be same or wider than parameter type float|null of accepting callable.',
			],
			[
				'Parameter #1 $cb of method CallablesWithoutCheckNullables\Foo::doBaz2() expects Closure(float|null): float, Closure(float): float given.',
				48,
				'Type float of parameter #1 $f of passed callable needs to be same or wider than parameter type float|null of accepting callable.',
			],
		];
		yield [false, true, $errors];
		yield [true, true, $errors];
	}

	/**
	 * @dataProvider dataCallablesWithoutCheckNullables
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testCallablesWithoutCheckNullables(bool $checkNullables, bool $checkUnionTypes, array $expectedErrors): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = $checkNullables;
		$this->checkUnionTypes = $checkUnionTypes;
		$this->analyse([__DIR__ . '/data/callables-without-check-nullables.php'], $expectedErrors);
	}

	public function testBug8713(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8713.php'], []);
	}

	public function testCannotCallOnGenericClassString(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/../Comparison/data/impossible-method-exists-on-generic-class-string.php'], [
			[
				'Cannot call method nonExistent() on class-string<ImpossibleMethodExistsOnGenericClassString\S>.',
				14,
			],
			[
				'Cannot call method staticAbc() on class-string<ImpossibleMethodExistsOnGenericClassString\S>.',
				20,
			],
			[
				'Cannot call method nonStaticAbc() on class-string<ImpossibleMethodExistsOnGenericClassString\S>.',
				25,
			],
			[
				'Cannot call method nonExistent() on class-string<ImpossibleMethodExistsOnGenericClassString\FinalS>.',
				35,
			],
			[
				'Cannot call method staticAbc() on class-string<ImpossibleMethodExistsOnGenericClassString\FinalS>.',
				41,
			],
			[
				'Cannot call method nonStaticAbc() on class-string<ImpossibleMethodExistsOnGenericClassString\FinalS>.',
				46,
			],
		]);
	}

	public function testBug8888(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8888.php'], []);
	}

	public function testBug9542(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-9542.php'], []);
	}

	public function testTrickyCallables(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/tricky-callables.php'], [
			[
				'Parameter #1 $cb of method TrickyCallables\Foo::doBar() expects callable(string|null): void, callable(string): void given.',
				13,
				'Type string of parameter #1 of passed callable needs to be same or wider than parameter type string|null of accepting callable.',
			],
			[
				'Parameter #1 $cb of method TrickyCallables\Bar::doBar() expects callable(string=): void, callable(string): void given.',
				34,
				'Parameter #1 of passed callable is required but the parameter of accepting callable is optional. It might be called without it.',
			],
			[
				'Parameter #1 $cb of method TrickyCallables\Baz::doBar() expects callable(): void, callable(string): void given.',
				55,
				'Parameter #1 of passed callable is required but accepting callable does not have that parameter. It will be called without it.',
			],
			[
				'Parameter #1 $filter of method TrickyCallables\TwoErrorsAtOnce::run() expects callable(int|string=): bool, Closure(int): true given.',
				83,
				'• Parameter #1 $key of passed callable is required but the parameter of accepting callable is optional. It might be called without it.
• Type int of parameter #1 $key of passed callable needs to be same or wider than parameter type int|string of accepting callable.',
			],
		]);
	}

	public function testObjectShapes(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/object-shapes.php'], [
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, stdClass given.',
				13,
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, Exception given.',
				14,
				'Exception might not have property $foo.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, object{foo: string, bar: int} given.',
				36,
				'Property ($foo) type int does not accept type string.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, object{foo?: int, bar: string} given.',
				37,
				'object{foo?: int, bar: string} might not have property $foo.',
			],
			[
				'Parameter #1 $std of method ObjectShapesAcceptance\Foo::requireStdClass() expects stdClass, object{foo: string, bar: int} given.',
				40,
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, object{foo: string, bar: int}&stdClass given.',
				43,
				'Property ($foo) type int does not accept type string.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, object given.',
				54,
				'• object might not have property $foo.
• object might not have property $bar.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Foo::doBar() expects object{foo: int, bar: string}, stdClass given.',
				55,
			],
			[
				'Parameter #1 $bar of method ObjectShapesAcceptance\Bar::requireBar() expects ObjectShapesAcceptance\Bar, object{a: int} given.',
				71,
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Bar::doBar() expects object{a: string}, ObjectShapesAcceptance\Bar given.',
				77,
				'Property ($a) type string does not accept type int.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Baz::doBar() expects object{a: int}, $this(ObjectShapesAcceptance\Baz) given.',
				105,
				'Property ObjectShapesAcceptance\Baz::$a is not public.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Baz::doBaz() expects object{b: int}, $this(ObjectShapesAcceptance\Baz) given.',
				106,
				'Property ObjectShapesAcceptance\Baz::$b is static.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Baz::doLorem() expects object{c: int}, $this(ObjectShapesAcceptance\Baz) given.',
				107,
				'Property ObjectShapesAcceptance\Baz::$c is not readable.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\Baz::doIpsum() expects object{d: array{foo: string}}, $this(ObjectShapesAcceptance\Baz) given.',
				108,
				'Property ($d) type array{foo: string} does not accept type array{foo: int}: Offset \'foo\' (string) does not accept type int.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\OptionalProperty::doBar() expects object{foo?: int}, object{foo?: string} given.',
				156,
				'Property ($foo) type int does not accept type string.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\OptionalProperty::doBaz() expects object{foo: int}, object{foo?: string} given.',
				157,
				'Property ($foo) type int does not accept type string.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\TestAcceptance::doFoo() expects object{foo: int}, Traversable given.',
				209,
				'Traversable might not have property $foo.',
			],
			[
				'Parameter #1 $o of method ObjectShapesAcceptance\TestAcceptance::doFoo() expects object{foo: int}, ObjectShapesAcceptance\FinalClass given.',
				210,
				PHP_VERSION_ID < 80200 ? 'ObjectShapesAcceptance\FinalClass might not have property $foo.' : 'ObjectShapesAcceptance\FinalClass does not have property $foo.',
			],
		]);
	}

	public function testBug9951(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-9951.php'], [
			[
				'Parameter #1 $field of method Bug9951\Cl::addCondition() expects array<int, Bug9951\AbstractScope|Bug9951\Expressionable>|Bug9951\AbstractScope|Bug9951\Expressionable|string, mixed given.',
				26,
			],
			[
				'Parameter #1 $field of method Bug9951\Cl::addCondition() expects array<int, Bug9951\AbstractScope|Bug9951\Expressionable>|Bug9951\AbstractScope|Bug9951\Expressionable|string, object|string|null given.',
				31,
			],
		]);
	}

	public function testTypedClassConstants(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/return-type-class-constant.php'], []);
	}

	public function testNamedParametersForMultiVariantFunctions(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/call-methods-named-params-multivariant.php'], [
			[
				'Unknown parameter $options in call to method XSLTProcessor::setParameter().',
				10,
			],
			[
				'Missing parameter $name (array) in call to method XSLTProcessor::setParameter().',
				10,
			],
			[
				'Unknown parameter $colno in call to method PDO::query().',
				15,
			],
			[
				'Unknown parameter $className in call to method PDO::query().',
				17,
			],
			[
				'Unknown parameter $constructorArgs in call to method PDO::query().',
				17,
			],
			[
				'Unknown parameter $className in call to method PDOStatement::setFetchMode().',
				22,
			],
		]);
	}

	public function testBug5518(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-5518.php'], []);
	}

	public function testRequireExtends(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/../Properties/data/require-extends.php'], [
			[
				'Call to an undefined method RequireExtends\MyInterface::doesNotExist().',
				43,
			],
		]);
	}

	public function testRequireImplements(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/../Properties/data/require-implements.php'], [
			[
				'Call to an undefined method RequireImplements\MyBaseClass::doesNotExist().',
				44,
			],
		]);
	}

	public function testBug6371(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-6371.php'], [
			[
				'Parameter #1 $t of method Bug6371\HelloWorld<int,string>::compare() expects int, true given.',
				24,
			],
			[
				'Parameter #2 $k of method Bug6371\HelloWorld<int,string>::compare() expects string, false given.',
				24,
			],
		]);
	}

	public function testBugTemplateMixedUnionIntersect(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-template-mixed-union-intersect.php'], [
			[
				'Call to an undefined method BugTemplateMixedUnionIntersect\FooInterface&T of mixed::bar().',
				17,
			],
			[
				'Call to an undefined method BugTemplateMixedUnionIntersect\FooInterface::bar().',
				20,
			],
			[
				'Cannot call method foo() on BugTemplateMixedUnionIntersect\FooInterface|T of mixed.',
				23,
			],
			[
				'Cannot call method foo() on mixed.',
				25,
			],
		]);
	}

	public function testBug9009(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-9009.php'], []);
	}

	public function testBuSplObjectStorageRemove(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-SplObjectStorage-remove.php'], [
			// removeNoIntersect should be reported, but unfortunately it cannot be expressed by the type system.
		]);
	}

	public function testClosureBindToParamClosureThis(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/closure-bind-to-param-closure-this.php'], [
			[
				'Parameter #1 $newThis of method Closure::bindTo() expects stdClass, ClosureBindToParamClosureThis\Foo given.',
				23,
			],
		]);
	}

	public function testPureCallable(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/pure-callable-accepts.php'], [
			[
				'Parameter #1 $cb of method PureCallableMethodAccepts\Foo::acceptsPureCallable() expects pure-callable(): mixed, callable(): mixed given.',
				33,
			],
			[
				'Parameter #1 $i of method PureCallableMethodAccepts\Foo::acceptsInt() expects int, callable given.',
				35,
			],
			[
				'Parameter #1 $i of method PureCallableMethodAccepts\Foo::acceptsInt() expects int, callable given.',
				36,
			],
			[
				'Parameter #1 $cb of method PureCallableMethodAccepts\Foo::acceptsPureCallable() expects pure-callable(): mixed, Closure(): 1 given.',
				41,
			],
			[
				'Parameter #1 $cb of method PureCallableMethodAccepts\Foo::acceptsPureClosure() expects pure-Closure, Closure(): 1 given.',
				61,
			],
		]);
	}

	public function testClosureParameterGenerics(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/closure-parameter-generics.php'], []);
	}

	public function testNoNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/no-named-arguments.php'], [
			[
				'Method NoNamedArgumentsMethod\Foo::doFoo() invoked with named argument $i, but it\'s not allowed because of @no-named-arguments.',
				32,
			],
			[
				'Method NoNamedArgumentsMethod\Bar::doFoo() invoked with named argument $i, but it\'s not allowed because of @no-named-arguments.',
				33,
			],
		]);
	}

	public function testTraitMixin(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/trait-mixin.php'], []);
	}

	public function testLowercaseString(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/lowercase-string.php'], [
			[
				'Parameter #1 $s of method LowercaseString\Bar::acceptLowercaseString() expects lowercase-string, \'NotLowerCase\' given.',
				26,
			],
			[
				'Parameter #1 $s of method LowercaseString\Bar::acceptLowercaseString() expects lowercase-string, string given.',
				28,
			],
			[
				'Parameter #1 $s of method LowercaseString\Bar::acceptLowercaseString() expects lowercase-string, numeric-string given.',
				30,
			],
		]);
	}

	public function testUppercaseString(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/uppercase-string.php'], [
			[
				'Parameter #1 $s of method UppercaseString\Bar::acceptUppercaseString() expects uppercase-string, \'NotUpperCase\' given.',
				26,
			],
			[
				'Parameter #1 $s of method UppercaseString\Bar::acceptUppercaseString() expects uppercase-string, string given.',
				28,
			],
			[
				'Parameter #1 $s of method UppercaseString\Bar::acceptUppercaseString() expects uppercase-string, numeric-string given.',
				30,
			],
		]);
	}

	public function testBug10159(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-10159.php'], []);
	}

	public function testBug1953(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-1953.php'], [
			[
				'Cannot call method bar() on string.',
				12,
			],
		]);
	}

	public function testBug11559c(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-11559c.php'], [
			[
				'Method class@anonymous/tests/PHPStan/Rules/Methods/data/bug-11559c.php:6:1::regular_fn() invoked with 3 parameters, 1 required.',
				15,
			],
		]);
	}

	public function testBug4801(): void
	{
		$this->checkThisOnly = false;
		$this->checkNullables = false;
		$this->checkUnionTypes = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-4801.php'], []);
	}

}
