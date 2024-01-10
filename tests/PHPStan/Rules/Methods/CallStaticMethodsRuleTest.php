<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallStaticMethodsRule>
 */
class CallStaticMethodsRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, $this->checkThisOnly, true, $this->checkExplicitMixed, false, true, false);
		return new CallStaticMethodsRule(
			new StaticMethodCallCheck($reflectionProvider, $ruleLevelHelper, new ClassCaseSensitivityCheck($reflectionProvider, true), true, true),
			new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new PhpVersion(80000), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true, true),
		);
	}

	public function testCallStaticMethods(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/call-static-methods.php'], [
			[
				'Call to an undefined static method CallStaticMethods\Foo::bar().',
				39,
			],
			[
				'Call to an undefined static method CallStaticMethods\Bar::bar().',
				40,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::bar().',
				41,
			],
			[
				'Static call to instance method CallStaticMethods\Foo::loremIpsum().',
				42,
			],
			[
				'Call to private static method dolor() of class CallStaticMethods\Foo.',
				43,
			],
			[
				'CallStaticMethods\Ipsum::ipsumTest() calls parent::lorem() but CallStaticMethods\Ipsum does not extend any class.',
				63,
			],
			[
				'Static method CallStaticMethods\Foo::test() invoked with 1 parameter, 0 required.',
				65,
			],
			[
				'Call to protected static method baz() of class CallStaticMethods\Foo.',
				66,
			],
			[
				'Call to static method loremIpsum() on an unknown class CallStaticMethods\UnknownStaticMethodClass.',
				67,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to private method __construct() of class CallStaticMethods\ClassWithConstructor.',
				87,
			],
			[
				'Method CallStaticMethods\ClassWithConstructor::__construct() invoked with 0 parameters, 1 required.',
				87,
			],
			[
				'Calling self::someStaticMethod() outside of class scope.',
				93,
			],
			[
				'Calling static::someStaticMethod() outside of class scope.',
				94,
			],
			[
				'Calling parent::someStaticMethod() outside of class scope.',
				95,
			],
			[
				'Call to protected static method baz() of class CallStaticMethods\Foo.',
				97,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::bar().',
				98,
			],
			[
				'Static call to instance method CallStaticMethods\Foo::loremIpsum().',
				99,
			],
			[
				'Call to private static method dolor() of class CallStaticMethods\Foo.',
				100,
			],
			[
				'Static method Locale::getDisplayLanguage() invoked with 3 parameters, 1-2 required.',
				104,
			],
			[
				'Static method CallStaticMethods\Foo::test() invoked with 3 parameters, 0 required.',
				115,
			],
			[
				'Cannot call static method foo() on int|string.',
				120,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				127,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::unknownMethod().',
				127,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				128,
			],
			[
				'Static call to instance method CallStaticMethods\Foo::loremIpsum().',
				128,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				129,
			],
			[
				'Call to private static method dolor() of class CallStaticMethods\Foo.',
				129,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				130,
			],
			[
				'Static method CallStaticMethods\Foo::test() invoked with 3 parameters, 0 required.',
				130,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				131,
			],
			[
				'Call to static method CallStaticMethods\Foo::test() with incorrect case: TEST',
				131,
			],
			[
				'Class CallStaticMethods\Foo referenced with incorrect case: CallStaticMethods\FOO.',
				132,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::__construct().',
				144,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::nonexistent().',
				154,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::nonexistent().',
				159,
			],
			[
				'Static call to instance method CallStaticMethods\Foo::loremIpsum().',
				160,
			],
			[
				'Static method CallStaticMethods\ClassOrString::calledMethod() invoked with 1 parameter, 0 required.',
				173,
			],
			[
				'Cannot call abstract static method CallStaticMethods\InterfaceWithStaticMethod::doFoo().',
				208,
			],
			[
				'Call to an undefined static method CallStaticMethods\InterfaceWithStaticMethod::doBar().',
				209,
			],
			[
				'Static call to instance method CallStaticMethods\InterfaceWithStaticMethod::doInstanceFoo().',
				212,
			],
			[
				'Static call to instance method CallStaticMethods\InterfaceWithStaticMethod::doInstanceFoo().',
				213,
			],
			[
				'Static method CallStaticMethods\Foo::test() invoked with 3 parameters, 0 required.',
				298,
			],
			[
				'Call to an undefined static method T of CallStaticMethods\Foo::nonexistentMethod().',
				299,
			],
			[
				'Static method CallStaticMethods\Foo::test() invoked with 3 parameters, 0 required.',
				302,
			],
			[
				'Call to an undefined static method CallStaticMethods\Foo::nonexistentMethod().',
				303,
			],
			[
				'Call to static method doFoo() on an unknown class CallStaticMethods\TraitWithStaticMethod.',
				328,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to an undefined static method static(CallStaticMethods\CallWithStatic)::nonexistent().',
				344,
			],
		]);
	}

	public function testCallInterfaceMethods(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/call-interface-methods.php'], [
			[
				'Cannot call abstract static method InterfaceMethods\Foo::fooStaticMethod().',
				26,
			],
			[
				'Call to an undefined static method InterfaceMethods\Baz::barStaticMethod().',
				27,
			],
		]);
	}

	public function testCallToIncorrectCaseMethodName(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/incorrect-static-method-case.php'], [
			[
				'Call to static method IncorrectStaticMethodCase\Foo::fooBar() with incorrect case: foobar',
				10,
			],
		]);
	}

	public function testStaticCallsToInstanceMethods(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/static-calls-to-instance-methods.php'], [
			[
				'Static call to instance method StaticCallsToInstanceMethods\Foo::doFoo().',
				10,
			],
			[
				'Static call to instance method StaticCallsToInstanceMethods\Bar::doBar().',
				16,
			],
			[
				'Static call to instance method StaticCallsToInstanceMethods\Foo::doFoo().',
				36,
			],
			[
				'Call to method StaticCallsToInstanceMethods\Foo::doFoo() with incorrect case: dofoo',
				42,
			],
			[
				'Method StaticCallsToInstanceMethods\Foo::doFoo() invoked with 1 parameter, 0 required.',
				43,
			],
			[
				'Call to private method doPrivateFoo() of class StaticCallsToInstanceMethods\Foo.',
				45,
			],
			[
				'Method StaticCallsToInstanceMethods\Foo::doFoo() invoked with 1 parameter, 0 required.',
				48,
			],
		]);
	}

	public function testStaticCallOnExpression(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/static-call-on-expression.php'], [
			[
				'Call to an undefined static method StaticCallOnExpression\Foo::doBar().',
				16,
			],
		]);
	}

	public function testStaticCallOnExpressionWithCheckDisabled(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/static-call-on-expression.php'], []);
	}

	public function testReturnStatic(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/return-static-static-method.php'], [
			[
				'Call to an undefined static method ReturnStaticStaticMethod\Bar::doBaz().',
				24,
			],
		]);
	}

	public function testCallParentAbstractMethod(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/call-parent-abstract-method.php'], [
			[
				'Cannot call abstract method CallParentAbstractMethod\Baz::uninstall().',
				21,
			],
			[
				'Cannot call abstract method CallParentAbstractMethod\Lorem::doFoo().',
				38,
			],
			[
				'Cannot call abstract method CallParentAbstractMethod\Lorem::doFoo().',
				48,
			],
			[
				'Cannot call abstract static method CallParentAbstractMethod\SitAmet::doFoo().',
				61,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/static-methods-class-exists.php'], []);
	}

	public function testBug3448(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-3448.php'], [
			[
				'Parameter #1 $lall of static method Bug3448\Foo::add() expects int, string given.',
				21,
			],
			[
				'Parameter #1 $lall of static method Bug3448\Foo::add() expects int, string given.',
				22,
			],
		]);
	}

	public function testBug3641(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-3641.php'], [
			[
				'Static method Bug3641\Foo::bar() invoked with 1 parameter, 0 required.',
				32,
			],
		]);
	}

	public function testBug2164(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-2164.php'], [
			[
				'Parameter #1 $arg of static method Bug2164\A::staticTest() expects static(Bug2164\B)|string, Bug2164\B|string given.',
				24,
			],
		]);
	}

	public function testNamedArguments(): void
	{
		$this->checkThisOnly = false;

		$this->analyse([__DIR__ . '/data/static-method-named-arguments.php'], [
			[
				'Missing parameter $j (int) in call to static method StaticMethodNamedArguments\Foo::doFoo().',
				15,
			],
			[
				'Unknown parameter $z in call to static method StaticMethodNamedArguments\Foo::doFoo().',
				16,
			],
		]);
	}

	public function testBug577(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-577.php'], []);
	}

	public function testBug4550(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-4550.php'], [
			[
				'Parameter #1 $class of static method Bug4550\Test::valuesOf() expects class-string<Person>, string given.',
				34,
			],
			[
				'Parameter #1 $class of static method Bug4550\Test::valuesOf() expects class-string<Person>, string given.',
				44,
			],
		]);
	}

	public function testBug1971(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$this->markTestSkipped('Test requires PHP 7.x');
		}

		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-1971.php'], [
			[
				'Parameter #1 $callback of static method Closure::fromCallable() expects callable(): mixed, array{class-string<static(Bug1971\\HelloWorld)>, \'sayHello2\'} given.',
				16,
			],
		]);
	}

	public function testBug1971Php8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-1971.php'], [
			[
				'Parameter #1 $callback of static method Closure::fromCallable() expects callable(): mixed, array{\'Bug1971\\\HelloWorld\', \'sayHello\'} given.',
				14,
			],
			[
				'Parameter #1 $callback of static method Closure::fromCallable() expects callable(): mixed, array{class-string<static(Bug1971\\HelloWorld)>, \'sayHello\'} given.',
				15,
			],
			[
				'Parameter #1 $callback of static method Closure::fromCallable() expects callable(): mixed, array{class-string<static(Bug1971\\HelloWorld)>, \'sayHello2\'} given.',
				16,
			],
		]);
	}

	public function testBug5259(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-5259.php'], []);
	}

	public function testBug5536(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-5536.php'], []);
	}

	public function testBug4886(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-4886.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		$this->checkThisOnly = false;

		// handled by a different rule
		$this->analyse([__DIR__ . '/data/first-class-static-method-callable.php'], []);
	}

	public function testBug5893(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5893.php'], []);
	}

	public function testBug6249(): void
	{
		// discussion https://github.com/phpstan/phpstan/discussions/6249
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6249.php'], []);
	}

	public function testBug5749(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5749.php'], []);
	}

	public function testBug5757(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5757.php'], []);
	}

	public function testDiscussion7004(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/discussion-7004.php'], [
			[
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray1() expects array<array{newsletterName: string, subscriberCount: int}>, array given.',
				46,
			],
			[
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray2() expects array{array{newsletterName: string, subscriberCount: int}}, array given.',
				47,
			],
			[
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray3() expects array{newsletterName: string, subscriberCount: int}, array given.',
				48,
			],
		]);
	}

	public function testTemplateTypeInOneBranchOfConditional(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/template-type-in-one-branch-of-conditional.php'], [
			[
				'Parameter #1 $params of static method TemplateTypeInOneBranchOfConditional\DriverManager::getConnection() expects array{wrapperClass?: class-string<TemplateTypeInOneBranchOfConditional\Connection>}, array{wrapperClass: \'stdClass\'} given.',
				27,
				"Offset 'wrapperClass' (class-string<TemplateTypeInOneBranchOfConditional\Connection>) does not accept type string.",
			],
			[
				'Unable to resolve the template type T in call to method static method TemplateTypeInOneBranchOfConditional\DriverManager::getConnection()',
				27,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
		]);
	}

	public function testBug7489(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7489.php'], []);
	}

	public function testHasMethodStaticCall(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/static-has-method.php'], [
			[
				'Call to an undefined static method StaticHasMethodCall\rex_var::doesNotExist().',
				38,
			],
			[
				'Call to an undefined static method StaticHasMethodCall\rex_var::doesNotExist().',
				48,
			],
		]);
	}

	public function testBug1267(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-1267.php'], []);
	}

	public function testBug6147(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-6147.php'], []);
	}

	public function testBug5781(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-5781.php'], [
			[
				'Parameter #1 $param of static method Bug5781\Foo::bar() expects array{a: bool, b: bool, c: bool, d: bool, e: bool, f: bool, g: bool, h: bool, ...}, array{} given.',
				17,
				"Array does not have offset 'a'.",
			],
		]);
	}

	public function testRequireExtends(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;

		$this->analyse([__DIR__ . '/../Properties/data/require-extends.php'], [
			[
				'Call to an undefined static method RequireExtends\MyInterface::doesNotExistStatic().',
				44,
			],
		]);
	}

	public function testRequireImplements(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;

		$this->analyse([__DIR__ . '/../Properties/data/require-implements.php'], [
			[
				'Call to an undefined static method RequireImplements\MyBaseClass::doesNotExistStatic().',
				45,
			],
		]);
	}

}
