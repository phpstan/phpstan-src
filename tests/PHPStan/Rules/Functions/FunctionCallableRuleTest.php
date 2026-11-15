<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<FunctionCallableRule>
 */
class FunctionCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		return new FunctionCallableRule(
			$reflectionProvider,
			new RuleLevelHelper(
				$reflectionProvider,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			self::getContainer()->getByType(FunctionCallParametersCheck::class),
			true,
			true,
		);
	}

	#[RequiresPhp('< 8.1.0')]
	public function testNotSupportedOnOlderVersions(): void
	{
		$this->analyse([__DIR__ . '/data/function-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-callable.php'], [
			[
				'Function nonexistent not found.',
				13,
			],
			[
				'Creating callable from string but it might not be a callable.',
				19,
			],
			[
				'Creating callable from 1 but it\'s not a callable.',
				33,
			],
			[
				'Call to function strlen() with incorrect case: StrLen',
				38,
			],
			[
				'Creating callable from 1|(callable(): mixed) but it might not be a callable.',
				47,
			],
			[
				'Creating callable from an unknown class FunctionCallable\Nonexistent.',
				52,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testDynamicCallForbidden(): void
	{
		$errors = [
			[
				'Calling the callable created from compact() throws, it cannot be called dynamically.',
				7,
			],
			[
				'Calling the callable created from extract() throws, it cannot be called dynamically.',
				8,
			],
			[
				'Calling the callable created from func_get_arg() throws, it cannot be called dynamically.',
				9,
			],
			[
				'Calling the callable created from func_get_args() throws, it cannot be called dynamically.',
				10,
			],
			[
				'Calling the callable created from func_num_args() throws, it cannot be called dynamically.',
				11,
			],
			[
				'Calling the callable created from get_defined_vars() throws, it cannot be called dynamically.',
				12,
			],
			[
				'Call to function compact() with incorrect case: COMPACT',
				13,
			],
			[
				'Calling the callable created from compact() throws, it cannot be called dynamically.',
				13,
			],
		];
		if (PHP_VERSION_ID < 80600) {
			$errors[] = [
				'Partial function application is supported only on PHP 8.6 and later.',
				14,
			];
			$errors[] = [
				'Partial function application is supported only on PHP 8.6 and later.',
				15,
			];
		} else {
			$errors[] = [
				'Cannot create callable from compact(), it cannot be called dynamically.',
				14,
			];
			$errors[] = [
				'Cannot create callable from extract(), it cannot be called dynamically.',
				15,
			];
		}

		$this->analyse([__DIR__ . '/data/function-callable-dynamic-call-forbidden.php'], $errors);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testPartialFunctionApplication(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80600) {
			$errors = [
				[
					'Partial function application is supported only on PHP 8.6 and later.',
					7,
				],
				[
					'Partial function application is supported only on PHP 8.6 and later.',
					8,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/function-partial-function-application.php'], $errors);
	}

	#[RequiresPhp('>= 8.6.0-dev')]
	public function testPartialFunctionApplicationArguments(): void
	{
		$this->analyse([__DIR__ . '/data/partial-function-application-arguments.php'], [
			[
				'Function PartialFunctionApplicationArguments\\doFoo partially applied with 2 arguments, at least 3 required.',
				35,
			],
			[
				'Function PartialFunctionApplicationArguments\\none partially applied with 1 argument, 0 required.',
				36,
			],
			[
				'Function PartialFunctionApplicationArguments\\none partially applied with 1 argument, 0 required.',
				37,
			],
			[
				'Function PartialFunctionApplicationArguments\\opt partially applied with 4 arguments, 1-3 required.',
				38,
			],
			[
				'Cannot use named placeholder for unknown or variadic parameter $x.',
				39,
			],
			[
				'Cannot use named placeholder for unknown or variadic parameter $d.',
				40,
			],
			[
				'Argument for parameter $a has already been passed.',
				41,
			],
			[
				'Argument for parameter $a has already been passed.',
				42,
			],
			[
				'Parameter #1 $a of function PartialFunctionApplicationArguments\\opt expects int, string given.',
				43,
			],
			[
				'Parameter #1 $a of function PartialFunctionApplicationArguments\\byRef is passed by reference, so it expects variables only.',
				44,
			],
			[
				'Cannot combine partial application and unpacking.',
				45,
			],
			[
				'Function strtok partially applied with 3 arguments, 1-2 required.',
				46,
			],
			[
				'Missing parameter $b (string) in partial application of function PartialFunctionApplicationArguments\\doFoo.',
				47,
			],
			[
				'Missing parameter $c (float) in partial application of function PartialFunctionApplicationArguments\\doFoo.',
				47,
			],
			[
				'Named argument cannot be followed by a positional argument.',
				47,
			],
			[
				'Variadic placeholder must be last.',
				48,
			],
			[
				'Parameter #1 $a of closure expects int, string given.',
				55,
			],
			[
				'Closure partially applied with 3 arguments, 2 required.',
				56,
			],
			[
				'Parameter #1 $a of function PartialFunctionApplicationArguments\\intOnly expects int, string given.',
				75,
			],
			[
				'Variadic placeholder must be last.',
				82,
			],
			[
				'Cannot combine partial application and unpacking.',
				83,
			],
			[
				'Variadic placeholder must be last.',
				84,
			],
			[
				'Variadic placeholder may only appear once.',
				89,
			],
			[
				'Variadic placeholder may only appear once.',
				90,
			],
			[
				'Variadic placeholder must be last.',
				91,
			],
			[
				'Function mt_rand partially applied with 1 argument, 2 required.',
				97,
			],
			[
				'Function mt_rand partially applied with 3 arguments, 0-2 required.',
				98,
			],
			[
				'Argument #2 ($filter_value) must be passed explicitly, because the default value is not known.',
				106,
			],
			[
				'Argument #2 ($filter_value) must be passed explicitly, because the default value is not known.',
				107,
			],
			[
				'Argument #2 ($max) must be passed explicitly, because the default value is not known.',
				108,
			],
			[
				'Argument #3 ($arg) must be passed explicitly, because the default value is not known.',
				109,
			],
		]);
	}

	public function testConditionallyExecutedCode(): void
	{
		$errors = [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				16,
			],
		];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'First-class callables are supported only on PHP 8.1 and later.',
				19,
			];
		}

		$this->analyse([__DIR__ . '/data/function-callable-php-versions.php'], $errors);
	}

}
