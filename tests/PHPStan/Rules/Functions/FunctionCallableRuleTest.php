<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

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

	public function testDynamicCallForbidden(): void
	{
		$errors = [
			[
				'Cannot create callable from compact(), it cannot be called dynamically.',
				7,
			],
			[
				'Cannot create callable from extract(), it cannot be called dynamically.',
				8,
			],
			[
				'Cannot create callable from func_get_arg(), it cannot be called dynamically.',
				9,
			],
			[
				'Cannot create callable from func_get_args(), it cannot be called dynamically.',
				10,
			],
			[
				'Cannot create callable from func_num_args(), it cannot be called dynamically.',
				11,
			],
			[
				'Cannot create callable from get_defined_vars(), it cannot be called dynamically.',
				12,
			],
			[
				'Call to function compact() with incorrect case: COMPACT',
				13,
			],
			[
				'Cannot create callable from compact(), it cannot be called dynamically.',
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
