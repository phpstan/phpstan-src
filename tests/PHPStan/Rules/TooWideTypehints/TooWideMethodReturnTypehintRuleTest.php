<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TooWideMethodReturnTypehintRule>
 */
class TooWideMethodReturnTypehintRuleTest extends RuleTestCase
{

	private bool $checkProtectedAndPublicMethods = true;

	private bool $alwaysCheckFinal = false;

	protected function getRule(): Rule
	{
		return new TooWideMethodReturnTypehintRule($this->checkProtectedAndPublicMethods, $this->alwaysCheckFinal);
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
		$this->analyse([__DIR__ . '/data/bug-5095.php'], [
			[
				'Method Bug5095\Parser::unaryOperatorFor() never returns \'not\' so it can be removed from the return type.',
				21,
			],
		]);
	}

	public function testBug6158(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-6158.php'], []);
	}

	public function testBug6175(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6175.php'], []);
	}

	public function dataAlwaysCheckFinal(): iterable
	{
		yield [
			false,
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
					'Method MethodTooWideReturnAlwaysCheckFinal\FooFinalMethods::test() never returns null so it can be removed from the return type.',
					48,
				],
			],
		];

		yield [
			true,
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
			false,
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

		yield [
			true,
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
	 * @dataProvider dataAlwaysCheckFinal
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	public function testAlwaysCheckFinal(bool $checkProtectedAndPublicMethods, bool $alwaysCheckFinal, array $expectedErrors): void
	{
		$this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
		$this->alwaysCheckFinal = $alwaysCheckFinal;
		$this->analyse([__DIR__ . '/data/method-too-wide-return-always-check-final.php'], $expectedErrors);
	}

}
