<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWideFunctionReturnTypehintRule>
 */
class TooWideFunctionReturnTypehintRuleTest extends RuleTestCase
{

	private bool $reportTooWideBool = false;

	private bool $reportNestedTooWideType = false;

	protected function getRule(): Rule
	{
		return new TooWideFunctionReturnTypehintRule(new TooWideTypeCheck(new PropertyReflectionFinder(), $this->reportTooWideBool, $this->reportNestedTooWideType));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/tooWideFunctionReturnType.php';
		$this->analyse([__DIR__ . '/data/tooWideFunctionReturnType.php'], [
			[
				'Function TooWideFunctionReturnType\bar() never returns string so it can be removed from the return type.',
				11,
			],
			[
				'Function TooWideFunctionReturnType\baz() never returns null so it can be removed from the return type.',
				15,
			],
			[
				'Function TooWideFunctionReturnType\ipsum() never returns null so it can be removed from the return type.',
				27,
			],
			[
				'Function TooWideFunctionReturnType\dolor2() never returns null so it can be removed from the return type.',
				41,
			],
			[
				'Function TooWideFunctionReturnType\dolor4() never returns int so it can be removed from the return type.',
				59,
			],
			[
				'Function TooWideFunctionReturnType\dolor6() never returns null so it can be removed from the return type.',
				79,
			],
			[
				'Function TooWideFunctionReturnType\conditionalType() never returns string so it can be removed from the return type.',
				90,
			],
		]);
	}

	public function testBug11980(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11980-function.php'], [
			[
				'Function Bug11980Function\process2() never returns void so it can be removed from the return type.',
				34,
			],
		]);
	}

	public function testBug10312a(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10312a.php'], []);
	}

	#[RequiresPhp('>= 8.2')]
	public function testBug13384cPhp82(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], [
			[
				'Function Bug13384c\doFoo() never returns true so the return type can be changed to false.',
				5,
			],
			[
				'Function Bug13384c\doFoo2() never returns false so the return type can be changed to true.',
				9,
			],
			[
				'Function Bug13384c\doFooPhpdoc() never returns false so the return type can be changed to true.',
				93,
			],
			[
				'Function Bug13384c\doFooPhpdoc2() never returns true so the return type can be changed to false.',
				100,
			],
			[
				'Function Bug13384c\returnsTrueUnionReturn() never returns int so it can be removed from the return type.',
				130,
			],
			[
				'Function Bug13384c\returnsTruePhpdocUnionReturn() never returns int so it can be removed from the return type.',
				137,
			],
		]);
	}

	#[RequiresPhp('< 8.2')]
	public function testBug13384cPrePhp82(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], [
			[
				'Function Bug13384c\doFooPhpdoc() never returns false so the return type can be changed to true.',
				93,
			],
			[
				'Function Bug13384c\doFooPhpdoc2() never returns true so the return type can be changed to false.',
				100,
			],
			[
				'Function Bug13384c\returnsTrueUnionReturn() never returns int so it can be removed from the return type.',
				130,
			],
			[
				'Function Bug13384c\returnsTruePhpdocUnionReturn() never returns int so it can be removed from the return type.',
				137,
			],
		]);
	}

	public function testBug13384cOff(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], [
			[
				'Function Bug13384c\returnsTrueUnionReturn() never returns int so it can be removed from the return type.',
				130,
			],
			[
				'Function Bug13384c\returnsTruePhpdocUnionReturn() never returns int so it can be removed from the return type.',
				137,
			],
		]);
	}

	public function testNestedTooWideType(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/nested-too-wide-function-return-type.php'], [
			[
				'Return type array<array{int, bool}> of function NestedTooWideFunctionReturnType\dataProvider() can be narrowed to array<array{int, false}>.',
				8,
				'Offset 1 (false) does not accept type bool.',
			],
		]);
	}

}
