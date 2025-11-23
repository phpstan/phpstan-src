<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;
use function usort;

/**
 * @extends RuleTestCase<CallStaticMethodsRule>
 */
class CallStaticMethodsRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, $this->checkThisOnly, true, $this->checkExplicitMixed, $this->checkImplicitMixed, false, true);
		$container = self::getContainer();
		return new CallStaticMethodsRule(
			new StaticMethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				true,
				true,
				true,
			),
			new FunctionCallParametersCheck(
				$ruleLevelHelper,
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				true,
				true,
				true,
				true,
			),
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
				'Call to an undefined static method CallStaticMethods\CallWithStatic::nonexistent().',
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

	#[RequiresPhp('>= 8.0')]
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

	#[RequiresPhp('< 8.0')]
	public function testBug1971(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-1971.php'], [
			[
				'Parameter #1 $callback of static method Closure::fromCallable() expects callable(): mixed, array{class-string<static(Bug1971\\HelloWorld)>, \'sayHello2\'} given.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug1971Php8(): void
	{
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
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray1() expects array<array{newsletterName: string, subscriberCount: int}>, array<mixed, mixed> given.',
				46,
			],
			[
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray2() expects array{array{newsletterName: string, subscriberCount: int}}, array<mixed, mixed> given.',
				47,
			],
			[
				'Parameter #1 $data of static method Discussion7004\Foo::fromArray3() expects array{newsletterName: string, subscriberCount: int}, array<mixed, mixed> given.',
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
				'Unable to resolve the template type T in call to static method TemplateTypeInOneBranchOfConditional\DriverManager::getConnection()',
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

	public function testBug8296(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-8296.php'], [
			[
				'Parameter #1 $objects of static method Bug8296\VerifyLoginTask::continueDump() expects array<string, object>, array<string, Bug8296\stdClass|true> given.',
				12,
			],
			[
				'Parameter #1 $string of static method Bug8296\VerifyLoginTask::stringByRef() expects string, int given.',
				15,
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

	public static function dataMixed(): array
	{
		$explicitOnlyErrors = [
			[
				'Cannot call static method foo() on mixed.',
				17,
			],
			[
				'Cannot call static method foo() on T of mixed.',
				26,
			],
			[
				'Parameter #1 $i of static method CallStaticMethodMixed\Bar::doBar() expects int, mixed given.',
				43,
			],
			[
				'Only iterables can be unpacked, mixed given in argument #1.',
				52,
			],
			[
				'Parameter #1 $i of static method CallStaticMethodMixed\Bar::doBar() expects int, T given.',
				81,
			],
			[
				'Only iterables can be unpacked, T of mixed given in argument #1.',
				84,
			],
			[
				'Parameter #1 $cb of static method CallStaticMethodMixed\CallableMixed::callAcceptsExplicitMixed() expects callable(mixed): void, Closure(int): void given.',
				134,
				'Type int of parameter #1 $i of passed callable needs to be same or wider than parameter type mixed of accepting callable.',
			],
			[
				'Parameter #1 $cb of static method CallStaticMethodMixed\CallableMixed::callReturnsInt() expects callable(): int, Closure(): mixed given.',
				161,
			],
			[
				'Parameter #1 $cb of static method CallStaticMethodMixed\CallableMixed::callReturnsInt() expects callable(): int, Closure(): mixed given.',
				168,
			],
		];
		$implicitOnlyErrors = [
			[
				'Cannot call static method foo() on mixed.',
				16,
			],
			[
				'Parameter #1 $i of static method CallStaticMethodMixed\Bar::doBar() expects int, mixed given.',
				42,
			],
			[
				'Only iterables can be unpacked, mixed given in argument #1.',
				51,
			],
		];
		$combinedErrors = array_merge($explicitOnlyErrors, $implicitOnlyErrors);
		usort($combinedErrors, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

		return [
			[
				true,
				false,
				$explicitOnlyErrors,
			],
			[
				false,
				true,
				$implicitOnlyErrors,
			],
			[
				true,
				true,
				$combinedErrors,
			],
			[
				false,
				false,
				[],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[RequiresPhp('>= 8.0')]
	#[DataProvider('dataMixed')]
	public function testMixed(bool $checkExplicitMixed, bool $checkImplicitMixed, array $errors): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->checkImplicitMixed = $checkImplicitMixed;
		$this->analyse([__DIR__ . '/data/call-static-method-mixed.php'], $errors);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBugWrongMethodNameWithTemplateMixed(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-wrong-method-name-with-template-mixed.php'], [
			[
				'Call to an undefined static method T of mixed&UnitEnum::from().',
				14,
			],
			[
				'Call to an undefined static method T of mixed&UnitEnum::from().',
				25,
			],
			[
				'Call to an undefined static method T of object&UnitEnum::from().',
				36,
			],
			[
				'Call to an undefined static method UnitEnum::from().',
				43,
			],
		]);
	}

	public function testConditionalParam(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/conditional-param.php'], [
			[
				'Parameter #1 $demoArg of static method ConditionalParam\HelloWorld::replaceCallback() expects string, true given.',
				16,
			],
			[
				'Parameter #1 $demoArg of static method ConditionalParam\HelloWorld::replaceCallback() expects bool, string given.',
				20,
			],
			[
				// wrong
				'Parameter #1 $demoArg of static method ConditionalParam\HelloWorld::replaceCallback() expects string, true given.',
				22,
			],
		]);
	}

	public function testClosureBindParamClosureThis(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/closure-bind-param-closure-this.php'], [
			[
				'Parameter #2 $newThis of static method Closure::bind() expects stdClass, ClosureBindParamClosureThis\Foo given.',
				25,
			],
		]);
	}

	public function testClosureBind(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/closure-bind.php'], [
			[
				'Parameter #3 $newScope of static method Closure::bind() expects \'static\'|class-string|object|null, \'CallClosureBind\\\Bar3\' given.',
				68,
			],
		]);
	}

	public function testBug10872(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-10872.php'], []);
	}

	public function testBug12015(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12015.php'], []);
	}

	public function testDynamicCall(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/dynamic-call.php'], [
			[
				'Call to an undefined static method MethodsDynamicCall\Foo::bar().',
				33,
			],
			[
				'Call to an undefined static method MethodsDynamicCall\Foo::doBar().',
				36,
			],
			[
				'Call to an undefined static method MethodsDynamicCall\Foo::doBuz().',
				36,
			],
			[
				'Parameter #1 $n of method MethodsDynamicCall\Foo::doFoo() expects int, int|string given.',
				58,
			],
			[
				'Parameter #1 $s of static method MethodsDynamicCall\Foo::doQux() expects string, int given.',
				59,
			],
			[
				'Parameter #1 $n of method MethodsDynamicCall\Foo::doFoo() expects int, string given.',
				60,
			],
		]);
	}

	public function testBug13267(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = false;

		$this->analyse([__DIR__ . '/data/bug-13267.php'], []);
	}

	public function testRestrictedInternalClassNameUsage(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/../InternalTag/data/static-method-call-on-internal-subclass.php'], [
			[
				'Call to static method doFoo() on internal class StaticMethodCallOnInternalSubclassOne\Bar.',
				33,
			],
		]);
	}

	public function testBug13556(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-13556.php'], []);
	}

	#[RequiresPhp('>= 8.5')]
	public function testPipeOperator(): void
	{
		$this->checkThisOnly = false;
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/static-call-pipe.php'], [
			[
				'Static method StaticCallPipe\Foo::doFoo() invoked with 1 parameter, 2 required.',
				20,
			],
			[
				'Parameter #1 $i of static method StaticCallPipe\Foo::doBar() expects int, string given.',
				24,
			],
			[
				'Parameter #1 $i of static method StaticCallPipe\Foo::doBar() expects int, null given.',
				26,
			],
			[
				'Static method StaticCallPipe\Foo::doFoo() invoked with 1 parameter, 2 required.',
				26,
			],
			[
				'Result of static method StaticCallPipe\Foo::doFoo() (void) is used.',
				26,
			],
			[
				'Result of static method StaticCallPipe\Foo::doBar() (void) is used.',
				28,
			],
		]);
	}

}
