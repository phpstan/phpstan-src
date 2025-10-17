<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWideMethodReturnTypehintRule>
 */
class TooWideMethodReturnTypehintRuleTest extends RuleTestCase
{

	private bool $checkProtectedAndPublicMethods = true;

	private bool $reportTooWideBool = false;

	private bool $reportNestedTooWideType = false;

	protected function getRule(): Rule
	{
		return new TooWideMethodReturnTypehintRule($this->checkProtectedAndPublicMethods, new TooWideTypeCheck(new PropertyReflectionFinder(), $this->reportTooWideBool, $this->reportNestedTooWideType));
	}

	public function testPrivate(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-private.php'], [
			[
				'Method TooWideMethodReturnType\Foo::bar() never returns string so it can be removed from the return type.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Foo::baz() never returns null so it can be removed from the return type.',
				18,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor() never returns null so it can be removed from the return type.',
				34,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor2() never returns null so it can be removed from the return type.',
				48,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor4() never returns int so it can be removed from the return type.',
				66,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor6() never returns null so it can be removed from the return type.',
				86,
			],
			[
				'Method TooWideMethodReturnType\ConditionalTypeClass::conditionalType() never returns string so it can be removed from the return type.',
				119,
			],
		]);
	}

	public function testPublicProtected(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected.php'], [
			[
				'Method TooWideMethodReturnType\Bar::bar() never returns string so it can be removed from the return type.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Bar::baz() never returns null so it can be removed from the return type.',
				18,
			],
			[
				'Method TooWideMethodReturnType\Bazz::lorem() never returns string so it can be removed from the return type.',
				35,
			],
		]);
	}

	public function testPublicProtectedWithInheritance(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected-inheritance.php'], [
			[
				'Method TooWideMethodReturnType\Baz::baz() never returns null so it can be removed from the return type.',
				27,
			],
			[
				'Method TooWideMethodReturnType\BarClass::doFoo() never returns null so it can be removed from the return type.',
				51,
			],
		]);
	}

	public function testBug5095(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5095.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug6158(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6158.php'], []);
	}

	public function testBug6175(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6175.php'], []);
	}

	public static function dataAlwaysCheckFinal(): iterable
	{
		yield [
			false,
			[
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\Foo::test() never returns null so it can be removed from the return type.',
					8,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test() never returns null so it can be removed from the return type.',
					28,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test2() never returns null so it can be removed from the return type.',
					33,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test3() never returns null so it can be removed from the return type.',
					38,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test() never returns null so it can be removed from the return type.',
					48,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test2() never returns null so it can be removed from the return type.',
					53,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test3() never returns null so it can be removed from the return type.',
					58,
				],
			],
		];

		yield [
			true,
			[
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\Foo::test() never returns null so it can be removed from the return type.',
					8,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test() never returns null so it can be removed from the return type.',
					28,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test2() never returns null so it can be removed from the return type.',
					33,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FinalFoo::test3() never returns null so it can be removed from the return type.',
					38,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test() never returns null so it can be removed from the return type.',
					48,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test2() never returns null so it can be removed from the return type.',
					53,
				],
				[
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test3() never returns null so it can be removed from the return type.',
					58,
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	#[DataProvider('dataAlwaysCheckFinal')]
	public function testAlwaysCheckFinal(bool $checkProtectedAndPublicMethods, array $expectedErrors): void
	{
		$this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
		$this->analyse([__DIR__ . '/data/method-too-wide-return-always-check-final.php'], $expectedErrors);
	}

	public function testBug11980(): void
	{
		$this->checkProtectedAndPublicMethods = true;
		$this->analyse([__DIR__ . '/data/bug-11980.php'], [
			[
				'Method Bug11980\Demo::process2() never returns void so it can be removed from the return type.',
				37,
			],
		]);
	}

	public function testBug10312(): void
	{
		$this->checkProtectedAndPublicMethods = true;
		$this->analyse([__DIR__ . '/data/bug-10312.php'], []);
	}

	public function testBug10312b(): void
	{
		$this->checkProtectedAndPublicMethods = true;
		$this->analyse([__DIR__ . '/data/bug-10312b.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10312c(): void
	{
		$this->checkProtectedAndPublicMethods = true;
		$this->analyse([__DIR__ . '/data/bug-10312c.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10312d(): void
	{
		$this->checkProtectedAndPublicMethods = true;
		$this->analyse([__DIR__ . '/data/bug-10312d.php'], []);
	}

	#[RequiresPhp('>= 8.2')]
	public function testBug13384c(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], [
			[
				'Method Bug13384c\Bug13384c::doBar() never returns true so the return type can be changed to false.',
				33,
			],
			[
				'Method Bug13384c\Bug13384c::doBar2() never returns false so the return type can be changed to true.',
				37,
			],
			[
				'Method Bug13384c\Bug13384c::doBarPhpdoc() never returns false so the return type can be changed to true.',
				55,
			],
			[
				'Method Bug13384c\Bug13384Static::doBar() never returns true so the return type can be changed to false.',
				62,
			],
			[
				'Method Bug13384c\Bug13384Static::doBar2() never returns false so the return type can be changed to true.',
				66,
			],
			[
				'Method Bug13384c\Bug13384Static::doBarPhpdoc() never returns false so the return type can be changed to true.',
				84,
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
				'Method Bug13384c\Bug13384c::doBarPhpdoc() never returns false so the return type can be changed to true.',
				55,
			],
			[
				'Method Bug13384c\Bug13384Static::doBarPhpdoc() never returns false so the return type can be changed to true.',
				84,
			],
		]);
	}

	public function testBug13384cOff(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], []);
	}

	public function testNestedTooWideType(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/nested-too-wide-method-return-type.php'], [
			[
				'Return type array<array{int, bool}> of method NestedTooWideMethodReturnType\Foo::dataProvider() can be narrowed to array<array{int, false}>.',
				11,
				'Offset 1 (false) does not accept type bool.',
			],
			[
				'Return type array<array{int|null}> of method NestedTooWideMethodReturnType\Foo::dataProvider2() can be narrowed to array<array{int}>.',
				28,
				'Offset 0 (int) does not accept type int|null.',
			],
			[
				'Return type array<string, bool|int|string> of method NestedTooWideMethodReturnType\WebhookTest::dataTest() can be narrowed to array<string, int|string|true>.',
				115,
			],
		]);
	}

	public function testBug13676(): void
	{
		$this->reportTooWideBool = true;
		$this->reportNestedTooWideType = true;
		$this->analyse([__DIR__ . '/data/bug-13676.php'], []);
	}

}
