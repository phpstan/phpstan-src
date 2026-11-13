<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MethodCallableRule>
 */
class MethodCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);

		return new MethodCallableRule(
			new MethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				checkFunctionNameCase: true,
				reportMagicMethods: true,
			),
			self::getContainer()->getByType(FunctionCallParametersCheck::class),
		);
	}

	#[RequiresPhp('< 8.1.0')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/method-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13596(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13596.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-callable.php'], [
			[
				'Call to method MethodCallable\Foo::doFoo() with incorrect case: dofoo',
				11,
			],
			[
				'Call to an undefined method MethodCallable\Foo::doNonexistent().',
				12,
			],
			[
				'Cannot call method doFoo() on int.',
				13,
			],
			[
				'Call to private method doBar() of class MethodCallable\Bar.',
				18,
			],
			[
				'Call to method doFoo() on an unknown class MethodCallable\Nonexistent.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Call to private method doFoo() of class MethodCallable\ParentClass.',
				53,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testPartialFunctionApplication(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80600) {
			$errors = [
				[
					'Partial function application is supported only on PHP 8.6 and later.',
					19,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/method-partial-function-application.php'], $errors);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-method-callable.php'], [
			[
				'Cannot combine nullsafe operator with Closure creation.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.6.0-dev')]
	public function testPartialFunctionApplicationArguments(): void
	{
		$this->analyse([__DIR__ . '/data/partial-function-application-method-arguments.php'], [
			[
				'Method PartialFunctionApplicationMethodArguments\\Foo::doFoo() partially applied with 1 argument, at least 2 required.',
				28,
			],
			[
				'Parameter #1 $a of method PartialFunctionApplicationMethodArguments\\Foo::doFoo() expects int, string given.',
				29,
			],
			[
				'Cannot use named placeholder for unknown or variadic parameter $c.',
				30,
			],
			[
				'Variadic placeholder must be last.',
				42,
			],
			[
				'Cannot combine partial application and unpacking.',
				43,
			],
			[
				'Cannot use named placeholder for unknown or variadic parameter $x.',
				72,
			],
			[
				'Variadic placeholder may only appear once.',
				78,
			],
			[
				'Cannot combine nullsafe operator with Closure creation.',
				80,
			],
		]);
	}

	public function testConditionallyExecutedCode(): void
	{
		$errors = [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				19,
			],
		];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'First-class callables are supported only on PHP 8.1 and later.',
				22,
			];
		}

		$this->analyse([__DIR__ . '/data/method-callable-php-versions.php'], $errors);
	}

}
