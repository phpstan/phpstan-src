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
				'Type $this(Bug3117\SimpleTemporal) is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
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
				'Template type T on class Bug4590\OkResponse is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Method Bug4590\\Controller::test2() should return Bug4590\\OkResponse<array<int, string>> but returns Bug4590\\OkResponse<array{string}>.',
				47,
				'Template type T on class Bug4590\OkResponse is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
			],
			[
				'Method Bug4590\\Controller::test3() should return Bug4590\\OkResponse<array<string>> but returns Bug4590\\OkResponse<array{string}>.',
				55,
				'Template type T on class Bug4590\OkResponse is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
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
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4803.php'], []);
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
				"• Type #1 from the union: Offset 'id' (null) does not accept type int.
• Type #2 from the union: Offset 'updated' (true) does not accept type false.",
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
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-7996.php'], []);
	}

	public function testBug6358(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6358.php'], [
			[
				'Method Bug6358\HelloWorld::sayHello() should return list<stdClass> but returns array{1: stdClass}.',
				14,
				'array{1: stdClass} is not a list.',
			],
		]);
	}

	public function testBug8071(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8071.php'], [
			[
				// there should be no errors
				'Method Bug8071\Inheritance::inherit() should return array<TKey of (int|string), TValues of bool|float|int|string|null> but returns array<string>.',
				17,
				'Type string is not always the same as TValues. It breaks the contract for some argument types, typically subtypes.',
			],
		]);
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
				"array<int<0, max>, '23423'> might not be a list.",
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

		$errors = [];
		if (PHP_VERSION_ID < 80300) {
			$errors = [
				[
					'Method Bug8223\HelloWorld::sayHello() should return DateTimeImmutable but returns (DateTimeImmutable|false).',
					11,
				],
				[
					'Method Bug8223\HelloWorld::sayHello2() should return array<DateTimeImmutable> but returns array<int, (DateTimeImmutable|false)>.',
					21,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/bug-8223.php'], $errors);
	}

	public function testBug8146bErrors(): void
	{
		$this->checkBenevolentUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-8146b-errors.php'], [
			[
				"Method Bug8146bError\LocationFixtures::getData() should return array<non-empty-string, array<non-empty-string, array{constituencies: non-empty-list<non-empty-string>, coordinates: array{lat: float, lng: float}}>> but returns array{Bács-Kiskun: array{Ágasegyháza: array{constituencies: array{'Bács-Kiskun 4.', true, false, Bug8146bError\X, null}, coordinates: array{lat: 46.8386043, lng: 19.4502899}}, Akasztó: array{constituencies: array{'Bács-Kiskun 3.'}, coordinates: array{lat: 46.6898175, lng: 19.205086}}, Apostag: array{constituencies: array{'Bács-Kiskun 3.'}, coordinates: array{lat: 46.8812652, lng: 18.9648478}}, Bácsalmás: array{constituencies: array{'Bács-Kiskun 5.'}, coordinates: array{lat: 46.1250396, lng: 19.3357509}}, Bácsbokod: array{constituencies: array{'Bács-Kiskun 6.'}, coordinates: array{lat: 46.1234737, lng: 19.155708}}, Bácsborsód: array{constituencies: array{'Bács-Kiskun 6.'}, coordinates: array{lat: 46.0989373, lng: 19.1566725}}, Bácsszentgyörgy: array{constituencies: array{'Bács-Kiskun 6.'}, coordinates: array{lat: 45.9746039, lng: 19.0398066}}, Bácsszőlős: array{constituencies: array{'Bács-Kiskun 5.'}, coordinates: array{lat: 46.1352003, lng: 19.4215997}}, ...}, Baranya: non-empty-array<literal-string&non-falsy-string, array<literal-string&non-falsy-string, array<int<0, max>|(literal-string&non-falsy-string), float|(literal-string&non-falsy-string)>>>, Békés: array{Almáskamarás: array{constituencies: array{'Békés 4.'}, coordinates: array{lat: 46.4617785, lng: 21.092448}}, Battonya: array{constituencies: array{'Békés 4.'}, coordinates: array{lat: 46.2902462, lng: 21.0199215}}, Békés: array{constituencies: array{'Békés 2.'}, coordinates: array{lat: 46.6704899, lng: 21.0434996}}, Békéscsaba: array{constituencies: array{'Békés 1.'}, coordinates: array{lat: 46.6735939, lng: 21.0877309}}, Békéssámson: array{constituencies: array{'Békés 4.'}, coordinates: array{lat: 46.4208677, lng: 20.6176498}}, Békésszentandrás: array{constituencies: array{'Békés 2.'}, coordinates: array{lat: 46.8715996, lng: 20.48336}}, Bélmegyer: array{constituencies: array{'Békés 3.'}, coordinates: array{lat: 46.8726019, lng: 21.1832832}}, Biharugra: array{constituencies: array{'Békés 3.'}, coordinates: array{lat: 46.9691009, lng: 21.5987651}}, ...}, Borsod-Abaúj-Zemplén: non-empty-array<literal-string&non-falsy-string, array<literal-string&non-falsy-string, array<int<0, max>|(literal-string&non-falsy-string), float|(literal-string&non-falsy-string)>>>, Budapest: array{Budapest I. ker.: array{constituencies: array{'Budapest 01.'}, coordinates: array{lat: 47.4968219, lng: 19.037458}}, Budapest II. ker.: array{constituencies: array{'Budapest 03.', 'Budapest 04.'}, coordinates: array{lat: 47.5393329, lng: 18.986934}}, Budapest III. ker.: array{constituencies: array{'Budapest 04.', 'Budapest 10.'}, coordinates: array{lat: 47.5671768, lng: 19.0368517}}, Budapest IV. ker.: array{constituencies: array{'Budapest 11.', 'Budapest 12.'}, coordinates: array{lat: 47.5648915, lng: 19.0913149}}, Budapest V. ker.: array{constituencies: array{'Budapest 01.'}, coordinates: array{lat: 47.5002319, lng: 19.0520181}}, Budapest VI. ker.: array{constituencies: array{'Budapest 05.'}, coordinates: array{lat: 47.509863, lng: 19.0625813}}, Budapest VII. ker.: array{constituencies: array{'Budapest 05.'}, coordinates: array{lat: 47.5027289, lng: 19.073376}}, Budapest VIII. ker.: array{constituencies: array{'Budapest 01.', 'Budapest 06.'}, coordinates: array{lat: 47.4894184, lng: 19.070668}}, ...}, Csongrád-Csanád: array{Algyő: array{constituencies: array{'Csongrád-Csanád 4.'}, coordinates: array{lat: 46.3329625, lng: 20.207889}}, Ambrózfalva: array{constituencies: array{'Csongrád-Csanád 4.'}, coordinates: array{lat: 46.3501417, lng: 20.7313995}}, Apátfalva: array{constituencies: array{'Csongrád-Csanád 4.'}, coordinates: array{lat: 46.173317, lng: 20.5800472}}, Árpádhalom: array{constituencies: array{'Csongrád-Csanád 3.'}, coordinates: array{lat: 46.6158286, lng: 20.547733}}, Ásotthalom: array{constituencies: array{'Csongrád-Csanád 2.'}, coordinates: array{lat: 46.1995983, lng: 19.7833756}}, Baks: array{constituencies: array{'Csongrád-Csanád 3.'}, coordinates: array{lat: 46.5518708, lng: 20.1064166}}, Balástya: array{constituencies: array{'Csongrád-Csanád 3.'}, coordinates: array{lat: 46.4261828, lng: 20.004933}}, Bordány: array{constituencies: array{'Csongrád-Csanád 2.'}, coordinates: array{lat: 46.3194213, lng: 19.9227063}}, ...}, Fejér: array{Aba: array{constituencies: array{'Fejér 5.'}, coordinates: array{lat: 47.0328193, lng: 18.522359}}, Adony: array{constituencies: array{'Fejér 4.'}, coordinates: array{lat: 47.119831, lng: 18.8612469}}, Alap: array{constituencies: array{'Fejér 5.'}, coordinates: array{lat: 46.8075763, lng: 18.684028}}, Alcsútdoboz: array{constituencies: array{'Fejér 3.'}, coordinates: array{lat: 47.4277067, lng: 18.6030325}}, Alsószentiván: array{constituencies: array{'Fejér 5.'}, coordinates: array{lat: 46.7910573, lng: 18.732161}}, Bakonycsernye: array{constituencies: array{'Fejér 2.'}, coordinates: array{lat: 47.321719, lng: 18.0907379}}, Bakonykúti: array{constituencies: array{'Fejér 2.'}, coordinates: array{lat: 47.2458464, lng: 18.195769}}, Balinka: array{constituencies: array{'Fejér 2.'}, coordinates: array{lat: 47.3135736, lng: 18.1907168}}, ...}, Győr-Moson-Sopron: array{Abda: array{constituencies: array{'Győr-Moson-Sopron 5.'}, coordinates: array{lat: 47.6962149, lng: 17.5445786}}, Acsalag: array{constituencies: array{'Győr-Moson-Sopron 3.'}, coordinates: array{lat: 47.676095, lng: 17.1977771}}, Ágfalva: array{constituencies: array{'Győr-Moson-Sopron 4.'}, coordinates: array{lat: 47.688862, lng: 16.5110233}}, Agyagosszergény: array{constituencies: array{'Győr-Moson-Sopron 3.'}, coordinates: array{lat: 47.608545, lng: 16.9409912}}, Árpás: array{constituencies: array{'Győr-Moson-Sopron 3.'}, coordinates: array{lat: 47.5134127, lng: 17.3931579}}, Ásványráró: array{constituencies: array{'Győr-Moson-Sopron 5.'}, coordinates: array{lat: 47.8287695, lng: 17.499195}}, Babót: array{constituencies: array{'Győr-Moson-Sopron 3.'}, coordinates: array{lat: 47.5752269, lng: 17.0758604}}, Bágyogszovát: array{constituencies: array{'Győr-Moson-Sopron 3.'}, coordinates: array{lat: 47.5866036, lng: 17.3617273}}, ...}, ...}.",
				12,
				"Offset 'constituencies' (non-empty-list<non-empty-string>) does not accept type array{'Bács-Kiskun 4.', true, false, Bug8146bError\X, null}.",
			],
		]);
	}

	public function testBug8573(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8573.php'], []);
	}

	public function testBug8879(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8879.php'], []);
	}

	public function testBug9011(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Method Bug9011\HelloWorld::getX() should return array<string> but returns false.',
					16,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-9011.php'], $errors);
	}

	public function testMagicSerialization(): void
	{
		$this->analyse([__DIR__ . '/data/magic-serialization.php'], [
			[
				'Method MagicSerialization\WrongSignature::__serialize() should return array but returns string.',
				23,
			],
			[
				'Method MagicSerialization\WrongSignature::__unserialize() with return type void returns string but should not return anything.',
				28,
			],
		]);
	}

	public function testBug7574(): void
	{
		$this->analyse([__DIR__ . '/../Classes/data/bug-7574.php'], []);
	}

	public function testMagicSignatures(): void
	{
		$this->analyse([__DIR__ . '/data/magic-signatures.php'], [
			[
				'Method MagicSignatures\WrongSignature::__isset() should return bool but returns string.',
				39,
			],
			[
				'Method MagicSignatures\WrongSignature::__clone() with return type void returns string but should not return anything.',
				43,
			],
			[
				'Method MagicSignatures\WrongSignature::__debugInfo() should return array|null but returns string.',
				47,
			],
			[
				'Method MagicSignatures\WrongSignature::__set() with return type void returns string but should not return anything.',
				51,
			],
			[
				'Method MagicSignatures\WrongSignature::__set_state() should return object but returns string.',
				55,
			],
			[
				'Method MagicSignatures\WrongSignature::__sleep() should return array<int, string> but returns string.',
				59,
			],
			[
				'Method MagicSignatures\WrongSignature::__unset() with return type void returns string but should not return anything.',
				63,
			],
			[
				'Method MagicSignatures\WrongSignature::__wakeup() with return type void returns string but should not return anything.',
				67,
			],
		]);
	}

	public function testLists(): void
	{
		$this->analyse([__DIR__ . '/data/return-list.php'], [
			[
				"Method ReturnList\Foo::getList1() should return list<string> but returns array{0?: 'foo', 1?: 'bar'}.",
				10,
				"array{0?: 'foo', 1?: 'bar'} might not be a list.",
			],
			[
				"Method ReturnList\Foo::getList2() should return list<string> but returns array{0?: 'foo', 1?: 'bar'}.",
				19,
				"array{0?: 'foo', 1?: 'bar'} might not be a list.",
			],
		]);
	}

	public function testConditionalListRule(): void
	{
		$this->analyse([__DIR__ . '/data/return-list-rule.php'], []);
	}

	public function testBug6856(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6856.php'], []);
	}

	public function testRuleError(): void
	{
		$this->analyse([__DIR__ . '/data/return-rule-error.php'], [
			[
				"Method ReturnRuleError\Bar::processNode() should return list<PHPStan\Rules\IdentifierRuleError> but returns array{'foo'}.",
				47,
				'Rules can no longer return plain strings. See: https://phpstan.org/blog/using-rule-error-builder',
			],
			[
				'Method ReturnRuleError\Baz::processNode() should return list<PHPStan\Rules\IdentifierRuleError> but returns array{PHPStan\Rules\RuleError}.',
				66,
				'Error is missing an identifier. See: https://phpstan.org/blog/using-rule-error-builder',
			],
			[
				'Method ReturnRuleError\Lorem::processNode() should return list<PHPStan\Rules\IdentifierRuleError> but returns array{1: PHPStan\Rules\IdentifierRuleError}.',
				88,
				'array{1: PHPStan\Rules\IdentifierRuleError} is not a list.',
			],
		]);
	}

	public function testBug6175(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6175.php'], []);
	}

	public function testBug9766(): void
	{
		$this->checkBenevolentUnionTypes = true;
		$this->analyse([__DIR__ . '/data/bug-9766.php'], []);
	}

	public function testWrongListTip(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-list-tip.php'], [
			[
				'Method WrongListTip\Test::doFoo() should return list<WrongListTip\Foo> but returns list<WrongListTip\Bar>.',
				23,
			],
			[
				'Method WrongListTip\Test2::doFoo() should return non-empty-array<WrongListTip\Foo> but returns non-empty-array<WrongListTip\Bar>.',
				44,
			],
			[
				'Method WrongListTip\Test3::doFoo() should return non-empty-list<WrongListTip\Foo> but returns array<WrongListTip\Bar>.',
				67,
				"• array<WrongListTip\Bar> might not be a list.\n• array<WrongListTip\Bar> might be empty.",
			],
		]);
	}

	public function testArrowFunctionReturningVoidClosure(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/arrow-function-returning-void-closure.php'], []);
	}

	public function testBug6653(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6653.php'], []);
	}

	public function testBug10291(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10291.php'], []);
	}

	public function testBug5008(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5008.php'], []);
	}

	public function testArrayPushPreservesList(): void
	{
		$this->analyse([__DIR__ . '/data/array-push-preserves-list.php'], []);
	}

	public function testBug10721(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10721.php'], []);
	}

	public function testBug11491(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11491.php'], []);
	}

	public function testBug3759(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3759.php'], []);
	}

	public function testBug11337(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11337.php'], []);
	}

	public function testBug10715(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10715.php'], []);
	}

	public function testBug11663(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11663.php'], []);
	}

	public function testBug11857(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11857-builder.php'], []);
	}

	public function testBug12223(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12223.php'], []);
	}

}
