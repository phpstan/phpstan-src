<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReturnTypeRule>
 */
class ReturnTypeRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkUnionTypes = true;

	private bool $checkBenevolentUnionTypes = false;

	protected function getRule(): Rule
	{
		return new ReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), true, false, $this->checkUnionTypes, $this->checkExplicitMixed, false, true, $this->checkBenevolentUnionTypes)));
	}

	public function testReturnTypeRule(): void
	{
		$this->analyse([__DIR__ . '/data/returnTypes.php'], [
			[
				'Method ReturnTypes\Foo::returnInteger() should return int but returns string.',
				20,
			],
			[
				'Method ReturnTypes\Foo::returnObject() should return ReturnTypes\Bar but returns int.',
				30,
			],
			[
				'Method ReturnTypes\Foo::returnObject() should return ReturnTypes\Bar but returns ReturnTypes\Foo.',
				34,
			],
			[
				'Method ReturnTypes\Foo::returnChild() should return ReturnTypes\Foo but returns ReturnTypes\OtherInterfaceImpl.',
				53,
			],
			[
				'Method ReturnTypes\Foo::returnVoid() with return type void returns null but should not return anything.',
				86,
			],
			[
				'Method ReturnTypes\Foo::returnVoid() with return type void returns int but should not return anything.',
				90,
			],
			[
				'Method ReturnTypes\Foo::returnStatic() should return static(ReturnTypes\Foo) but returns ReturnTypes\FooParent.',
				106,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns ReturnTypes\Foo.',
				139,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns ReturnTypes\Bar.',
				147,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns array<int, ReturnTypes\Bar>.',
				151,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns int.',
				155,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but empty return statement found.',
				159,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns iterable<ReturnTypes\Bar>&ReturnTypes\Collection.',
				166,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns iterable<ReturnTypes\Foo>&ReturnTypes\AnotherCollection.',
				173,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns (iterable<ReturnTypes\Foo>&ReturnTypes\AnotherCollection)|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection).',
				180,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns iterable<ReturnTypes\Bar>&ReturnTypes\AnotherCollection.',
				187,
			],
			[
				'Method ReturnTypes\Foo::returnUnionIterableType() should return array<ReturnTypes\Foo>|(iterable<ReturnTypes\Foo>&ReturnTypes\Collection) but returns null.',
				191,
			],
			[
				'Method ReturnTypes\Foo::returnThis() should return $this(ReturnTypes\Foo) but returns ReturnTypes\Foo.',
				219,
			],
			[
				'Method ReturnTypes\Foo::returnThis() should return $this(ReturnTypes\Foo) but returns int.',
				222,
			],
			[
				'Method ReturnTypes\Foo::returnThis() should return $this(ReturnTypes\Foo) but returns static(ReturnTypes\Foo).',
				225,
			],
			[
				'Method ReturnTypes\Foo::returnThis() should return $this(ReturnTypes\Foo) but returns null.',
				229,
			],
			[
				'Method ReturnTypes\Foo::returnThisOrNull() should return $this(ReturnTypes\Foo)|null but returns ReturnTypes\Foo.',
				247,
			],
			[
				'Method ReturnTypes\Foo::returnThisOrNull() should return $this(ReturnTypes\Foo)|null but returns int.',
				250,
			],
			[
				'Method ReturnTypes\Foo::returnThisOrNull() should return $this(ReturnTypes\Foo)|null but returns static(ReturnTypes\Foo).',
				259,
			],
			[
				'Method ReturnTypes\Foo::returnsParent() should return ReturnTypes\FooParent but returns int.',
				282,
			],
			[
				'Method ReturnTypes\Foo::returnsParent() should return ReturnTypes\FooParent but returns null.',
				285,
			],
			[
				'Method ReturnTypes\Foo::returnsPhpDocParent() should return ReturnTypes\FooParent but returns int.',
				298,
			],
			[
				'Method ReturnTypes\Foo::returnsPhpDocParent() should return ReturnTypes\FooParent but returns null.',
				301,
			],
			[
				'Method ReturnTypes\Foo::returnScalar() should return bool|float|int|string but returns stdClass.',
				323,
			],
			[
				'Method ReturnTypes\Foo::returnsNullInTernary() should return int but returns int|null.',
				342,
			],
			[
				'Method ReturnTypes\Foo::returnsNullInTernary() should return int but returns int|null.',
				348,
			],
			[
				'Method ReturnTypes\Foo::misleadingBoolReturnType() should return ReturnTypes\boolean but returns true.',
				355,
			],
			[
				'Method ReturnTypes\Foo::misleadingBoolReturnType() should return ReturnTypes\boolean but returns int.',
				358,
			],
			[
				'Method ReturnTypes\Foo::misleadingIntReturnType() should return ReturnTypes\integer but returns int.',
				368,
			],
			[
				'Method ReturnTypes\Foo::misleadingIntReturnType() should return ReturnTypes\integer but returns true.',
				371,
			],
			[
				'Method ReturnTypes\Stock::getAnotherStock() should return ReturnTypes\Stock but returns ReturnTypes\Stock|null.',
				429,
			],
			[
				'Method ReturnTypes\Stock::returnSelfAgainError() should return ReturnTypes\Stock but returns ReturnTypes\Stock|null.',
				484,
			],
			[
				'Method ReturnTypes\Stock::returnYetSelfAgainError() should return ReturnTypes\Stock but returns ReturnTypes\Stock|null.',
				508,
			],
			[
				'Method ReturnTypes\ReturningSomethingFromConstructor::__construct() with return type void returns ReturnTypes\Foo but should not return anything.',
				552,
			],
			[
				'Method ReturnTypes\ReturnTernary::returnTernary() should return ReturnTypes\Foo but returns false.',
				627,
			],
			[
				'Method ReturnTypes\TrickyVoid::returnVoidOrInt() should return int|void but returns string.',
				658,
			],
			[
				'Method ReturnTypes\TernaryWithJsonEncode::toJson() should return string but returns string|false.',
				689,
			],
			[
				'Method ReturnTypes\AppendedArrayReturnType::foo() should return array<int> but returns array<int, stdClass>.',
				702,
			],
			[
				'Method ReturnTypes\AppendedArrayReturnType::bar() should return array<int> but returns array<int|stdClass>.',
				712,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__toString() should return string but returns true.',
				722,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__isset() should return bool but returns int.',
				727,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__destruct() with return type void returns int but should not return anything.',
				732,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__unset() with return type void returns int but should not return anything.',
				737,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__sleep() should return array<int, string> but returns array<int, stdClass>.',
				742,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__wakeup() with return type void returns int but should not return anything.',
				749,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__set_state() should return object but returns array<string, string>.',
				754,
			],
			[
				'Method ReturnTypes\WrongMagicMethods::__clone() with return type void returns int but should not return anything.',
				759,
			],
			[
				'Method ReturnTypes\ArrayFillKeysIssue::getIPs2() should return array<string, array<ReturnTypes\Foo>> but returns array<string, array<int, ReturnTypes\Bar>>.',
				817,
			],
			[
				'Method ReturnTypes\AssertThisInstanceOf::doBar() should return $this(ReturnTypes\AssertThisInstanceOf) but returns ReturnTypes\AssertThisInstanceOf&ReturnTypes\FooInterface.',
				840,
			],
			[
				'Method ReturnTypes\NestedArrayCheck::doFoo() should return array<string, bool> but returns array<string, array<int, string>>.',
				860,
			],
			[
				'Method ReturnTypes\NestedArrayCheck::doBar() should return array<string, bool> but returns array<string, array<string, string>>.',
				875,
			],
			[
				'Method ReturnTypes\Foo2::returnIntFromParent() should return int but returns string.',
				950,
			],
			[
				'Method ReturnTypes\Foo2::returnIntFromParent() should return int but returns ReturnTypes\integer.',
				953,
			],
			[
				'Method ReturnTypes\VariableOverwrittenInForeach::doFoo() should return int but returns int|string.',
				1011,
			],
			[
				'Method ReturnTypes\VariableOverwrittenInForeach::doBar() should return int but returns int|string.',
				1026,
			],
			[
				'Method ReturnTypes\ReturnStaticGeneric::instanceReturnsStatic() should return static(ReturnTypes\ReturnStaticGeneric) but returns ReturnTypes\ReturnStaticGeneric.',
				1066,
			],
			[
				'Method ReturnTypes\NeverReturn::doFoo() should never return but return statement found.',
				1241,
			],
			[
				'Method ReturnTypes\NeverReturn::doBaz3() should never return but return statement found.',
				1254,
			],
		]);
	}

	public function testMisleadingTypehintsInClassWithoutNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/misleadingTypehints.php'], [
			[
				'Method FooWithoutNamespace::misleadingBoolReturnType() should return boolean but returns true.',
				9,
			],
			[
				'Method FooWithoutNamespace::misleadingBoolReturnType() should return boolean but returns int.',
				13,
			],
			[
				'Method FooWithoutNamespace::misleadingIntReturnType() should return integer but returns int.',
				24,
			],
			[
				'Method FooWithoutNamespace::misleadingIntReturnType() should return integer but returns true.',
				28,
			],
		]);
	}

	public function testOverridenTypeFromIfConditionShouldNotBeMixedAfterBranch(): void
	{
		$this->analyse([__DIR__ . '/data/returnTypes-overridenTypeInIfCondition.php'], [
			[
				'Method ReturnTypes\OverridenTypeInIfCondition::getAnotherAnotherStock() should return ReturnTypes\Stock but returns ReturnTypes\Stock|null.',
				15,
			],
		]);
	}

	public function testReturnStaticFromParent(): void
	{
		$this->analyse([__DIR__ . '/data/return-static-from-parent.php'], []);
	}

	public function testReturnIterable(): void
	{
		$this->analyse([__DIR__ . '/data/returnTypes-iterable.php'], [
			[
				'Method ReturnTypesIterable\Foo::stringIterable() should return iterable<string> but returns array<int, int>.',
				27,
			],
			[
				'Method ReturnTypesIterable\Foo::stringIterablePipe() should return iterable<string> but returns array<int, int>.',
				36,
			],
		]);
	}

	public function testBug2676(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2676.php'], []);
	}

	public function testBug2885(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2885.php'], []);
	}

	public function testMergeInheritedPhpDocs(): void
	{
		$this->analyse([__DIR__ . '/data/merge-inherited-return.php'], [
			[
				'Method ReturnTypePhpDocMergeReturnInherited\ParentClass::method() should return ReturnTypePhpDocMergeReturnInherited\B but returns ReturnTypePhpDocMergeReturnInherited\A.',
				33,
			],
			[
				'Method ReturnTypePhpDocMergeReturnInherited\ChildClass::method() should return ReturnTypePhpDocMergeReturnInherited\B but returns ReturnTypePhpDocMergeReturnInherited\A.',
				41,
			],
			[
				'Method ReturnTypePhpDocMergeReturnInherited\ChildClass2::method() should return ReturnTypePhpDocMergeReturnInherited\D but returns ReturnTypePhpDocMergeReturnInherited\B.',
				52,
			],
		]);
	}

	public function testReturnTypeRulePhp70(): void
	{
		$this->analyse([__DIR__ . '/data/returnTypes-7.0.php'], [
			[
				'Method ReturnTypes\FooPhp70::returnInteger() should return int but empty return statement found.',
				10,
			],
		]);
	}

	public function testBug3997(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3997.php'], [
			[
				"Method Bug3997\Foo::count() should return int<0, max> but returns 'foo'.",
				13,
			],
			[
				"Method Bug3997\Bar::count() should return int<0, max> but returns 'foo'.",
				24,
			],
			[
				'Method Bug3997\Baz::count() should return int but returns string.',
				38,
			],
			[
				'Method Bug3997\Lorem::count() should return int but returns string.',
				52,
			],
			[
				'Method Bug3997\Dolor::count() should return int<0, max> but returns -1.',
				78,
			],
		]);
	}

	public function testBug1903(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1903.php'], [
			[
				'Method Bug1903\Test::doFoo() should return array but returns int.',
				19,
			],
		]);
	}

	public function testBug3117(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3117.php'], [
			[
				'Method Bug3117\SimpleTemporal::adjustInto() should return T of Bug3117\Temporal but returns $this(Bug3117\SimpleTemporal).',
				35,
			],
		]);
	}

	public function testBug3034(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3034.php'], []);
	}

	public function testBug3951(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3951.php'], []);
	}

	public function testInferArrayKey(): void
	{
		$this->analyse([__DIR__ . '/data/infer-array-key.php'], []);
	}

	public function testBug4590(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4590.php'], [
			[
				'Method Bug4590\\Controller::test1() should return Bug4590\\OkResponse<array<string, string>> but returns Bug4590\\OkResponse<array{ok: string}>.',
				39,
			],
			[
				'Method Bug4590\\Controller::test2() should return Bug4590\\OkResponse<array<int, string>> but returns Bug4590\\OkResponse<array{string}>.',
				47,
			],
			[
				'Method Bug4590\\Controller::test3() should return Bug4590\\OkResponse<array<string>> but returns Bug4590\\OkResponse<array{string}>.',
				55,
			],
		]);
	}

	public function testTemplateStringBound(): void
	{
		$this->analyse([__DIR__ . '/data/template-string-bound.php'], []);
	}

	public function testBug4605(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4605.php'], []);
	}

	public function testReturnStatic(): void
	{
		$this->analyse([__DIR__ . '/data/return-static.php'], []);
	}

	public function testBug4648(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4648.php'], []);
	}

	public function testBug3523(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3523.php'], [
			[
				'Method Bug3523\Bar::deserialize() should return static(Bug3523\Bar) but returns Bug3523\Bar.',
				31,
			],
		]);
	}

	public function testBug3120(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3120.php'], []);
	}

	public function testBug3118(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3118.php'], [
			[
				'Method Bug3118\CustomEnum2::all() should return Bug3118\EnumSet<static(Bug3118\CustomEnum2)> but returns Bug3118\CustomEnumSet.',
				56,
			],
		]);
	}

	public function testBug4795(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4795.php'], []);
	}

	public function testBug4803(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-4803.php'], []);
	}

	public function testBug7020(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7020.php'], []);
	}

	public function testBug2573(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2573-return.php'], []);
	}

	public function testBug4603(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4603.php'], []);
	}

	public function testBug3151(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3151.php'], []);
	}

	public function testTemplateUnion(): void
	{
		$this->analyse([__DIR__ . '/data/return-template-union.php'], [
			[
				'Method ReturnTemplateUnion\Foo::doFoo2() should return T of bool|float|int|string but returns (T of bool|float|int|string)|null.',
				25,
			],
		]);
	}

	public function dataBug5218(): array
	{
		return [
			[
				true,
				[
					[
						'Method Bug5218\IA::getIterator() should return Traversable<string, int> but returns ArrayIterator<string, mixed>.',
						14,
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
	 * @dataProvider dataBug5218
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testBug5218(bool $checkExplicitMixed, array $errors): void
	{
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->analyse([__DIR__ . '/data/bug-5218.php'], $errors);
	}

	public function testBug5979(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5979.php'], []);
	}

	public function testBug4165(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4165.php'], []);
	}

	public function testBug6053(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6053.php'], []);
	}

	public function testBug6438(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6438.php'], []);
	}

	public function testBug6589(): void
	{
		$this->checkUnionTypes = false;
		$this->analyse([__DIR__ . '/data/bug-6589.php'], [
			[
				'Method Bug6589\HelloWorldTemplated::getField() should return TField of Bug6589\Field2 but returns Bug6589\Field.',
				17,
			],
			[
				'Method Bug6589\HelloWorldSimple::getField() should return Bug6589\Field2 but returns Bug6589\Field.',
				31,
			],
		]);
	}

	public function testBug6418(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6418.php'], []);
	}

	public function testBug6230(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6230.php'], []);
	}

	public function testBug5860(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5860.php'], []);
	}

	public function testBug6266(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6266.php'], []);
	}

	public function testBug6023(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6023.php'], []);
	}

	public function testBug5065(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-5065.php'], []);
	}

	public function testBug5065ExplicitMixed(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5065.php'], [
			[
				'Method Bug5065\Collection::emptyWorkaround2() should return Bug5065\Collection<NewTKey of (int|string), NewT> but returns Bug5065\Collection<(int|string), mixed>.',
				60,
			],
		]);
	}

	public function testBug3400(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-3400.php'], []);
	}

	public function testBug6353(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6353.php'], []);
	}

	public function testBug6635Level9(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6635.php'], []);
	}

	public function testBug6635Level8(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-6635.php'], []);
	}

	public function testBug6552(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6552.php'], []);
	}

	public function testConditionalTypes(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/return-rule-conditional-types.php'], [
			[
				'Method ReturnRuleConditionalTypes\Foo::doFoo() should return int|string but returns stdClass.',
				15,
			],
			[
				'Method ReturnRuleConditionalTypes\Bar::doFoo() should return int|string but returns stdClass.',
				29,
			],
			[
				'Method ReturnRuleConditionalTypes\Bar2::doFoo() should return int|string but returns stdClass.',
				43,
			],
		]);
	}

	public function testBug7265(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7265.php'], []);
	}

	public function testBug7460(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7460.php'], []);
	}

	public function testBug4117(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-4117.php'], []);
	}

	public function testBug5232(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-5232.php'], []);
	}

	public function testBug7511(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7511.php'], []);
	}

	public function testTaggedUnions(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/tagged-unions.php'], [
			[
				'Method TaggedUnionReturnCheck\HelloWorld::sayHello() should return array{updated: false, id: null}|array{updated: true, id: int} but returns array{updated: false, id: 5}.',
				12,
			],
		]);
	}

	public function testBug7904(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7904.php'], []);
	}

	public function testBug7996(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-7996.php'], []);
	}

	public function testBug6358(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6358.php'], [
			[
				'Method Bug6358\HelloWorld::sayHello() should return list<stdClass> but returns array{1: stdClass}.',
				14,
			],
		]);
	}

	public function testBug8071(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8071.php'], []);
	}

	public function testBug3499(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-3499.php'], []);
	}

	public function testBug8174(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8174.php'], [
			[
				"Method Bug8174\HelloWorld::filterList() should return list<string> but returns array<int<0, max>, '23423'>.",
				21,
			],
		]);
	}

	public function testBug7519(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7519.php'], []);
	}

	public function testBug8223(): void
	{
		$this->checkBenevolentUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-8223.php'], [
			[
				'Method Bug8223\HelloWorld::sayHello() should return DateTimeImmutable but returns (DateTimeImmutable|false).',
				11,
			],
			[
				'Method Bug8223\HelloWorld::sayHello2() should return array<DateTimeImmutable> but returns array<int, (DateTimeImmutable|false)>.',
				21,
			],
		]);
	}

}
