<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReturnTypeRule>
 */
class ReturnTypeRuleTest extends RuleTestCase
{

	private bool $checkNullables;

	private bool $checkExplicitMixed;

	protected function getRule(): Rule
	{
		return new ReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), $this->checkNullables, false, true, $this->checkExplicitMixed, false)));
	}

	public function testReturnTypeRule(): void
	{
		require_once __DIR__ . '/data/returnTypes.php';
		$this->checkNullables = true;
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/returnTypes.php'], [
			[
				'Function ReturnTypes\returnInteger() should return int but returns string.',
				17,
			],
			[
				'Function ReturnTypes\returnObject() should return ReturnTypes\Bar but returns int.',
				27,
			],
			[
				'Function ReturnTypes\returnObject() should return ReturnTypes\Bar but returns ReturnTypes\Foo.',
				31,
			],
			[
				'Function ReturnTypes\returnChild() should return ReturnTypes\Foo but returns ReturnTypes\OtherInterfaceImpl.',
				50,
			],
			[
				'Function ReturnTypes\returnVoid() with return type void returns null but should not return anything.',
				83,
			],
			[
				'Function ReturnTypes\returnVoid() with return type void returns int but should not return anything.',
				87,
			],
			[
				'Function ReturnTypes\returnFromGeneratorString() should return string but empty return statement found.',
				152,
			],
			[
				'Function ReturnTypes\returnFromGeneratorString() should return string but returns int.',
				155,
			],
			[
				'Function ReturnTypes\returnVoidFromGenerator2() with return type void returns int but should not return anything.',
				173,
			],
			[
				'Function ReturnTypes\returnNever() should never return but return statement found.',
				181,
			],
		]);
	}

	public function testReturnTypeRulePhp70(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/returnTypes-7.0.php'], [
			[
				'Function ReturnTypes\Php70\returnInteger() should return int but empty return statement found.',
				7,
			],
		]);
	}

	public function testIsGenerator(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/is-generator.php'], []);
	}

	public function testBug2568(): void
	{
		require_once __DIR__ . '/data/bug-2568.php';
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-2568.php'], []);
	}

	public function testBug2723(): void
	{
		require_once __DIR__ . '/data/bug-2723.php';
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-2723.php'], [
			[
				"Function Bug2723\baz() should return Bug2723\Bar<Bug2723\Foo<T4>> but returns Bug2723\BarOfFoo<'hello'>.",
				55,
			],
		]);
	}

	public function testBug5706(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-5706.php'], []);
	}

	public function testBug5844(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-5844.php'], []);
	}

	public function testBug7218(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-7218.php'], []);
	}

	public function testBug5751(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-5751.php'], []);
	}

	public function testBug3931(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-3931.php'], []);
	}

	public function testBug3801(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/bug-3801.php'], [
			[
				'Function Bug3801\do_foo() should return array{bool, null}|array{null, bool} but returns array{false, true}.',
				17,
			],
			[
				'Function Bug3801\do_foo() should return array{bool, null}|array{null, bool} but returns array{false, false}.',
				21,
			],
		]);
	}

	public function testListWithNullablesChecked(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = true;
		$this->analyse([__DIR__ . '/data/return-list-nullables.php'], [
			[
				'Function ReturnListNullables\doFoo() should return array<string>|null but returns array<int, string|null>.',
				16,
			],
		]);
	}

	public function testListWithNullablesUnchecked(): void
	{
		$this->checkExplicitMixed = false;
		$this->checkNullables = false;
		$this->analyse([__DIR__ . '/data/return-list-nullables.php'], []);
	}

}
