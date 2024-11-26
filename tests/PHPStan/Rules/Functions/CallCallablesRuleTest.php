<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallCallablesRule>
 */
class CallCallablesRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, false, false);
		return new CallCallablesRule(
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
			$ruleLevelHelper,
			true,
		);
	}

	public function testRule(): void
	{
		$errors = [
			[
				'Trying to invoke string but it might not be a callable.',
				17,
			],
			[
				'Callable \'date\' invoked with 0 parameters, 1-2 required.',
				21,
			],
			[
				'Trying to invoke \'nonexistent\' but it\'s not a callable.',
				25,
			],
			[
				'Parameter #1 $i of callable array{$this(CallCallables\\Foo), \'doBar\'} expects int, string given.',
				33,
			],
			[
				'Callable array{\'CallCallables\\\\Foo\', \'doStaticBaz\'} invoked with 1 parameter, 0 required.',
				39,
			],
			[
				'Callable \'CallCallables\\\\Foo:…\' invoked with 1 parameter, 0 required.',
				41,
			],
			[
				'Call to private method privateFooMethod() of class CallCallables\Foo.',
				52,
			],
			[
				'Closure invoked with 0 parameters, 1-2 required.',
				58,
			],
			[
				'Result of closure (void) is used.',
				59,
			],
			[
				'Closure invoked with 0 parameters, at least 1 required.',
				64,
			],
			[
				'Parameter #1 $i of closure expects int, string given.',
				70,
			],
			[
				'Parameter #1 $str of callable class@anonymous/tests/PHPStan/Rules/Functions/data/callables.php:75 expects string, int given.',
				81,
			],
			[
				'Trying to invoke \'\' but it\'s not a callable.',
				86,
			],
			[
				'Invoking callable on an unknown class CallCallables\Bar.',
				90,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Parameter #1 ...$foo of closure expects CallCallables\Foo, array<CallCallables\Foo> given.',
				106,
			],
			[
				'Trying to invoke CallCallables\Baz but it might not be a callable.',
				113,
			],
			[
				'Trying to invoke array{object, \'bar\'} but it might not be a callable.',
				131,
			],
			[
				'Closure invoked with 0 parameters, 3 required.',
				146,
			],
			[
				'Closure invoked with 1 parameter, 3 required.',
				147,
			],
			[
				'Closure invoked with 2 parameters, 3 required.',
				148,
			],
			[
				'Trying to invoke array{object, \'yo\'} but it might not be a callable.',
				163,
			],
			[
				'Trying to invoke array{object, \'yo\'} but it might not be a callable.',
				167,
			],
			[
				'Trying to invoke array{\'CallCallables\\\\CallableInForeach\', \'bar\'|\'foo\'} but it might not be a callable.',
				179,
			],
			[
				'Trying to invoke array{\'CallCallables\\\\ConstantArrayUnionCallables\'|\'DateTimeImmutable\', \'doFoo\'} but it might not be a callable.',
				205,
			],
			[
				'Trying to invoke array{\'CallCallables\\\ConstantArrayUnionCallables\', \'doBaz\'|\'doFoo\'} but it might not be a callable.',
				212,
			],
		];

		if (PHP_VERSION_ID >= 80000) {
			$errors[] = [
				'Trying to invoke array{\'CallCallables\\\ConstantArrayUnionCallables\'|\'CallCallables\\\ConstantArrayUnionCallablesTest\', \'doBar\'|\'doFoo\'} but it\'s not a callable.',
				220,
			];
		}

		$this->analyse([__DIR__ . '/data/callables.php'], $errors);
	}

	public function testNamedArguments(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/callables-named-arguments.php'], [
			[
				'Missing parameter $j (int) in call to closure.',
				14,
			],
			[
				'Unknown parameter $i in call to callable callable(int, int): void.',
				23,
			],
			[
				'Missing parameter $ (int) in call to callable callable(int, int): void.',
				23,
			],
			[
				'Missing parameter $j (int) in call to callable callable(int, int): void.',
				24,
			],
			[
				'Unknown parameter $z in call to callable callable(int, int): void.',
				25,
			],
		]);
	}

	public function dataBug3566(): array
	{
		return [
			[
				true,
				[
					[
						'Parameter #1 of closure expects int, TMemberType given.',
						29,
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
	 * @dataProvider dataBug3566
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testBug3566(bool $checkExplicitMixed, array $errors): void
	{
		$this->checkExplicitMixed = $checkExplicitMixed;
		$this->analyse([__DIR__ . '/data/bug-3566.php'], $errors);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/callables-nullsafe.php'], [
			[
				'Parameter #1 $val of closure expects int, int|null given.',
				18,
			],
		]);
	}

	public function testBug1849(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1849.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		$this->analyse([__DIR__ . '/data/call-first-class-callables.php'], [
			[
				'Unable to resolve the template type T in call to closure',
				14,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
			[
				'Unable to resolve the template type T in call to closure',
				17,
				'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
			],
		]);
	}

	public function testBug6701(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6701.php'], [
			[
				'Parameter #1 $test of closure expects string|null, int given.',
				14,
			],
			[
				'Parameter #1 $test of closure expects string|null, int given.',
				18,
			],
			[
				'Parameter #1 $test of closure expects string|null, int given.',
				24,
			],
		]);
	}

	public function testStaticCallInFunctions(): void
	{
		$this->analyse([__DIR__ . '/data/static-call-in-functions.php'], []);
	}

	public function testBug5867(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5867.php'], []);
	}

	public function testBug6485(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6485.php'], [
			[
				'Parameter #1 of closure expects never, TBlockType of Bug6485\Block given.',
				33,
			],
		]);
	}

	public function testBug6633(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6633.php'], []);
	}

	public function testBug3818b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3818b.php'], []);
	}

	public function testBug9594(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9594.php'], []);
	}

	public function testBug9614(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9614.php'], []);
	}

	public function testBug10814(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10814.php'], [
			[
				'Parameter #1 of closure expects DateTime, DateTimeImmutable given.',
				10,
			],
		]);
	}

}
