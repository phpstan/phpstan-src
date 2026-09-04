<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ParameterOutAssignedTypeRule>
 */
class ParameterOutAssignedTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ParameterOutAssignedTypeRule(
			new ParameterOutTypeCheck(
				new RuleLevelHelper(
					self::createReflectionProvider(),
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: true,
					checkImplicitMixed: false,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/parameter-out-assigned-type.php'], [
			[
				'Parameter &$p @param-out type of function ParameterOutAssignedType\foo() expects int, string given.',
				10,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutAssignedType\Foo::doFoo() expects int, string given.',
				21,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutAssignedType\Foo::doBar() expects string, int given.',
				29,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutAssignedType\Foo::doBaz() expects list<int>, array<0|int<2, max>, int> given.',
				38,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutAssignedType\Foo::doBaz2() expects list<int>, non-empty-list<\'str\'|int> given.',
				47,
			],
			[
				'Parameter &$p @param-out type of method ParameterOutAssignedType\Foo::doBaz3() expects list<list<int>>, list<array<int<0, max>, int>> given.',
				56,
			],
			[
				'Parameter &$p by-ref type of method ParameterOutAssignedType\Foo::doNoParamOut() expects string, int given.',
				61,
				'You can change the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

	public function testBug10699(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10699.php'], []);
	}

	public function testBenevolentArrayKey(): void
	{
		$this->analyse([__DIR__ . '/data/benevolent-array-key.php'], []);
	}

	public function testBug13093(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13093.php'], []);
	}

	public function testBug13093b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13093b.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12754(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12754.php'], []);
	}

	public function testBug14124(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14124.php'], []);
	}

	public function testBug14124b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14124b.php'], []);
	}

	public function testCatchVariable(): void
	{
		$this->analyse([__DIR__ . '/data/parameter-out-catch-variable.php'], [
			[
				'Parameter &$p @param-out type of function ParameterOutCatchVariable\foo() expects int, Exception given.',
				11,
			],
			[
				'Parameter &$p by-ref type of function ParameterOutCatchVariable\bar() expects int, Exception given.',
				19,
				'You can change the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15066(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15066.php'], [
			[
				'Parameter &$refs by-ref type of function Bug15066Variables\\variadicWrongType() expects string|null, int|string|null given.',
				22,
				'You can change the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Parameter &$refs by-ref type of method Bug15066Variables\\Foo::variadicWrongType() expects string|null, int|string|null given.',
				52,
				'You can change the parameter out type with @param-out PHPDoc tag.',
			],
			// rebinding the packed variable to a non-array is silent, to an array is not - see the fixture
			[
				'Parameter &$refs by-ref type of function Bug15066Variables\\variadicRebindWrongArray() expects string|null, int given.',
				69,
				'You can change the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

}
