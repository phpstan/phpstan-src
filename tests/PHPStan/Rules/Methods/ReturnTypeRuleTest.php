<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReturnTypeRule>
 */
class ReturnTypeRuleTest extends RuleTestCase
{

	private bool $checkNullables = true;

	private bool $checkExplicitMixed = false;

	private bool $checkUnionTypes = true;

	private bool $checkBenevolentUnionTypes = false;

	protected function getRule(): Rule
	{
		return new ReturnTypeRule(new FunctionReturnTypeCheck(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: $this->checkNullables,
				checkThisOnly: false,
				checkUnionTypes: $this->checkUnionTypes,
				checkExplicitMixed: $this->checkExplicitMixed,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: $this->checkBenevolentUnionTypes,
				discoveringSymbolsTip: true,
			),
		));
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
				'Method ReturnTypes\ArrayFillKeysIssue::getIPs2() should return array<string, array<ReturnTypes\Foo>> but returns array<string, list<ReturnTypes\Bar>>.',
				817,
			],
			[
				'Method ReturnTypes\AssertThisInstanceOf::doBar() should return $this(ReturnTypes\AssertThisInstanceOf) but returns ReturnTypes\AssertThisInstanceOf&ReturnTypes\FooInterface.',
				839,
			],
			[
				'Method ReturnTypes\NestedArrayCheck::doFoo() should return array<string, bool> but returns array<string, list<string>>.',
				859,
			],
			[
				'Method ReturnTypes\NestedArrayCheck::doBar() should return array<string, bool> but returns array<string, array<string, string>>.',
				874,
			],
			[
				'Method ReturnTypes\Foo2::returnIntFromParent() should return int but returns string.',
				949,
			],
			[
				'Method ReturnTypes\Foo2::returnIntFromParent() should return int but returns ReturnTypes\integer.',
				952,
			],
			[
				'Method ReturnTypes\VariableOverwrittenInForeach::doFoo() should return int but returns string.',
				1010,
			],
			[
				'Method ReturnTypes\VariableOverwrittenInForeach::doBar() should return int but returns int|string.',
				1025,
			],
			[
				'Method ReturnTypes\ReturnStaticGeneric::instanceReturnsStatic() should return static(ReturnTypes\ReturnStaticGeneric) but returns ReturnTypes\ReturnStaticGeneric.',
				1065,
			],
			[
				'Method ReturnTypes\NeverReturn::doFoo() should never return but return statement found.',
				1240,
			],
			[
				'Method ReturnTypes\NeverReturn::doBaz3() should never return but return statement found.',
				1253,
			],
		]);
	}

	public function testMisleadingMixedType(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$errors = [];
		} else {
			$errors = [
				[
					'Method MethodMisleadingMixedReturn\Foo::misleadingMixedReturnType() should return MethodMisleadingMixedReturn\mixed but returns int.',
					11,
				],
				[
					'Method MethodMisleadingMixedReturn\Foo::misleadingMixedReturnType() should return MethodMisleadingMixedReturn\mixed but returns true.',
					14,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/method-misleading-mixed-return.php'], $errors);
	}

	#[RequiresPhp('>= 8.0.0')]
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

	public function testBug8636(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8636.php'], []);
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
				'Method Bug4590\OkResponse::testGenericStaticMismatch() should return static(Bug4590\OkResponse<array<int, string>>) but returns static(Bug4590\OkResponse<array{ok: \'hello\'}>).',
				44,
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

	#[RequiresPhp('>= 8.0.0')]
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

	public static function dataBug5218(): array
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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataBug5218')]
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

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7904(): void
	{
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

	#[RequiresPhp('>= 8.0.0')]
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
				'Method Bug8146bError\\LocationFixtures::getData() should return array<non-empty-string, array<non-empty-string, array{constituencies: non-empty-list<non-empty-string>, coordinates: array{lat: float, lng: float, ...}}>> but returns non-empty-array<literal-string&non-falsy-string, non-empty-array<literal-string&non-falsy-string, non-empty-array<literal-string&non-falsy-string, non-empty-array<int<0, max>|(literal-string&non-falsy-string), bool|Bug8146bError\\X|float|(literal-string&non-falsy-string)|null>>>>.',
				12,
				"• Offset 'Bács-Kiskun' › 'Ágasegyháza' › 'constituencies' (non-empty-list<non-empty-string>) does not accept type array{'Bács-Kiskun 4.', true, false, Bug8146bError\X, null}.\n• Offsets 'Baranya' and 'Borsod-Abaúj-Zemplén' (array<non-empty-string, array{constituencies: non-empty-list<non-empty-string>, coordinates: array{lat: float, lng: float, ...}}>) does not accept type non-empty-array<literal-string&non-falsy-string, non-empty-array<literal-string&non-falsy-string, non-empty-array<int<0, max>|(literal-string&non-falsy-string), float|(literal-string&non-falsy-string)>>>: Sealed array shape can only accept a constant array. Extra keys are not allowed.",
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testNestedArrayShapeReturnReasons(): void
	{
		$this->analyse([__DIR__ . '/data/nested-array-shape-return-reasons.php'], [
			[
				'Method NestedArrayShapeReturnReasons\\CapacityHelperTest::capacitiesData() should return array<string, array{list<array{int, int, int, list<int>, bool}>, int, list<NestedArrayShapeReturnReasons\\ChildData>, bool}> but returns non-empty-array<literal-string&non-falsy-string, array{non-empty-list<array{int, int, int, list<int>, bool, bool}>, int, list<NestedArrayShapeReturnReasons\\ChildData>, bool}>.',
				71,
				"• Offsets 'case 0' › 0 › 0, 'case 2' › 0 › 1, 'case 3' › 0 › 0 and 4 more (array{int, int, int, list<int>, bool}): Sealed array shape does not accept array with extra key 5 => true.\n• Offsets 'case 1' › 0 › 0, 'case 2' › 0 › 0, 'case 4' › 0 › 1 and 2 more (array{int, int, int, list<int>, bool}): Sealed array shape does not accept array with extra key 5 => false.",
			],
		]);
	}

	public function testBug8573(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8573.php'], []);
	}

	public function testBug8632(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8632.php'], []);
	}

	public function testBug7857(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7857.php'], []);
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
				'Method MagicSerialization\WrongSignature::__serialize() should return array<mixed, mixed> but returns string.',
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
				'Method MagicSignatures\WrongSignature::__debugInfo() should return array<mixed, mixed>|null but returns string.',
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

	public function testBug10653(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10653.php'], []);
	}

	public function testBug4163(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4163.php'], [
			[
				'Method Bug4163\HelloWorld::lall() should return array<string, string> but returns array<int|string, string>.',
				28,
			],
		]);
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

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/property-hooks-return.php'], [
			[
				'Get hook for property PropertyHooksReturn\Foo::$i should return int but returns string.',
				11,
			],
			[
				'Set hook for property PropertyHooksReturn\Foo::$i with return type void returns int but should not return anything.',
				21,
			],
			[
				'Get hook for property PropertyHooksReturn\Foo::$s should return non-empty-string but returns \'\'.',
				29,
			],
			[
				'Get hook for property PropertyHooksReturn\GenericFoo::$a should return T of PropertyHooksReturn\Foo but returns PropertyHooksReturn\Foo.',
				48,
				'Type PropertyHooksReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property PropertyHooksReturn\GenericFoo::$b should return T of PropertyHooksReturn\Foo but returns PropertyHooksReturn\Foo.',
				63,
				'Type PropertyHooksReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property PropertyHooksReturn\GenericFoo::$c should return T of PropertyHooksReturn\Foo but returns PropertyHooksReturn\Foo.',
				73,
				'Type PropertyHooksReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testShortGetPropertyHook(): void
	{
		$this->analyse([__DIR__ . '/data/short-get-property-hook-return.php'], [
			[
				'Get hook for property ShortGetPropertyHookReturn\Foo::$i should return int but returns string.',
				9,
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\Foo::$s should return non-empty-string but returns \'\'.',
				18,
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$a should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				36,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$b should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				50,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$c should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				59,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug1O580(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10580.php'], [
			[
				'Method Bug10580\FooA::fooThisInterface() should return $this(Bug10580\FooA) but returns Bug10580\FooA.',
				18,
			],
			[
				'Method Bug10580\FooA::fooThisClass() should return $this(Bug10580\FooA) but returns Bug10580\FooA.',
				19,
			],
			[
				'Method Bug10580\FooA::fooThisSelf() should return $this(Bug10580\FooA) but returns Bug10580\FooA.',
				20,
			],
			[
				'Method Bug10580\FooA::fooThisStatic() should return $this(Bug10580\FooA) but returns Bug10580\FooA.',
				21,
			],
			[
				'Method Bug10580\FooB::fooThisInterface() should return $this(Bug10580\FooB) but returns Bug10580\FooB.',
				27,
			],
			[
				'Method Bug10580\FooB::fooThisClass() should return $this(Bug10580\FooB) but returns Bug10580\FooB.',
				29,
			],
			[
				'Method Bug10580\FooB::fooThisSelf() should return $this(Bug10580\FooB) but returns Bug10580\FooB.',
				31,
			],
			[
				'Method Bug10580\FooB::fooThisStatic() should return $this(Bug10580\FooB) but returns Bug10580\FooB.',
				33,
			],
			[
				'Method Bug10580\FooB::fooThis() should return $this(Bug10580\FooB) but returns Bug10580\FooB.',
				35,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12927(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12927.php'], []);
	}

	public function testBug4443(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4443.php'], [
			[
				'Method Bug4443\HelloWorld::getArray() should return array<mixed> but returns array<mixed>|null.',
				22,
			],
		]);
	}

	public function testBug13043(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13043.php'], []);
	}

	public function testBug12739(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12739.php'], []);
	}

	public function testBug12928(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12928.php'], [
			[
				'Method Bug12928\FooBarBaz::render() should return non-empty-string but returns string.',
				59,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7225(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7225.php'], []);
	}

	public function testDeepDimFetch(): void
	{
		$this->analyse([__DIR__ . '/data/deep-dim-fetch.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9494(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9494.php'], []);
	}

	public function testBug11160(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11160.php'], []);
	}

	public function testBug8438(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8438.php'], []);
	}

	public function testBug10771(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10771.php'], []);
	}

	public function testBug5946(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5946.php'], [
			[
				'Method Bug5946\Model::getModel() should return $this(Bug5946\Model) but returns static(Bug5946\Model).',
				21,
			],
			[
				'Method Bug5946\Model::getModel() should return $this(Bug5946\Model) but returns static(Bug5946\Model).',
				23,
			],
		]);
	}

	public function testBug9669(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9669.php'], []);
	}

	public function testBug10924(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10924.php'], []);
	}

	public function testBug11430(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-11430.php'], []);
	}

	public function testBug12653(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12653.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testBug14553(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14553.php'], []);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug12110(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12110.php'], []);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug11776(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11776.php'], []);
	}

	public function testBug14893(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14893.php'], []);
	}

	public function testBug12704(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12704.php'], []);
	}

	public function testBug12576(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12576.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14647(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-14647.php'], []);
	}

	public function testBug12601(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12601.php'], []);
	}

	public function testBug12490(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12490.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug12420(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12420.php'], [
			[
				'Method Bug12420\Test::testFailingArray() should return array<\'bar\'|\'foo\'> but returns array{\'foo\', \'bar\', \'wrong\'}.',
				66,
			],
		]);
	}

	public function testBug11835(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-11835.php'], []);
	}

	public function testBug11435(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11435.php'], []);
	}

	public function testBug10289(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10289.php'], []);
	}

	public function testBug12984(): void
	{
		$this->checkNullables = false;
		$this->analyse([__DIR__ . '/data/bug-12984.php'], []);
	}

	public function testBug13325(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13325.php'], []);
	}

}
