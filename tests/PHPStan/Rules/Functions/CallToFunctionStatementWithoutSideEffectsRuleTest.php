<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithoutSideEffectsRule>
 */
class CallToFunctionStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithoutSideEffectsRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-statement-no-side-effects.php'], [
			[
				'Call to function sprintf() on a separate line has no effect.',
				13,
			],
			[
				'Call to function var_export() on a separate line has no effect.',
				24,
			],
			[
				'Call to function print_r() on a separate line has no effect.',
				26,
			],
		]);

		if (PHP_VERSION_ID < 80000) {
			return;
		}

		$this->analyse([__DIR__ . '/data/function-call-statement-no-side-effects-8.0.php'], [
			[
				'Call to function var_export() on a separate line has no effect.',
				19,
			],
			[
				'Call to function print_r() on a separate line has no effect.',
				20,
			],
			[
				'Call to function highlight_string() on a separate line has no effect.',
				21,
			],
		]);
	}

	public function testPhpDoc(): void
	{
		require_once __DIR__ . '/data/function-call-statement-no-side-effects-phpdoc-definition.php';
		$this->analyse([__DIR__ . '/data/function-call-statement-no-side-effects-phpdoc.php'], [
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pure1() on a separate line has no effect.',
				8,
			],
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pure2() on a separate line has no effect.',
				9,
			],
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pure3() on a separate line has no effect.',
				10,
			],
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pure4() on a separate line has no effect.',
				11,
			],
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pure5() on a separate line has no effect.',
				12,
			],
			[
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pureAndThrowsVoid() on a separate line has no effect.',
				13,
			],
		]);
	}

	public function testBug12224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12224.php'], []);
	}

	public function testBug4455(): void
	{
		require_once __DIR__ . '/data/bug-4455.php';
		$this->analyse([__DIR__ . '/data/bug-4455.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		$this->analyse([__DIR__ . '/data/first-class-callable-function-without-side-effect.php'], [
			[
				'Call to function mkdir() on a separate line has no effect.',
				12,
			],
			[
				'Call to function strlen() on a separate line has no effect.',
				24,
			],
			[
				'Call to function FirstClassCallableFunctionWithoutSideEffect\foo() on a separate line has no effect.',
				36,
			],
			[
				'Call to function FirstClassCallableFunctionWithoutSideEffect\bar() on a separate line has no effect.',
				49,
			],
		]);
	}

	public function testBug11101(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11101.php'], [
			[
				'Call to function array_filter() on a separate line has no effect.',
				13,
			],
			[
				'Call to function array_map() on a separate line has no effect.',
				14,
			],
			[
				'Call to function array_reduce() on a separate line has no effect.',
				15,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug11101Php84(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11101-php84.php'], [
			[
				'Call to function array_find() on a separate line has no effect.',
				13,
			],
			[
				'Call to function array_find_key() on a separate line has no effect.',
				14,
			],
			[
				'Call to function array_any() on a separate line has no effect.',
				15,
			],
			[
				'Call to function array_all() on a separate line has no effect.',
				16,
			],
		]);
	}

	public function testPureUnlessCallableIsImpure(): void
	{
		require_once __DIR__ . '/data/pure-unless-callable-is-impure-definition.php';
		$this->analyse([__DIR__ . '/data/pure-unless-callable-is-impure.php'], [
			[
				'Call to function PureUnlessCallableIsImpure\myFilter() on a separate line has no effect.',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-without-side-effect-pipe.php'], [
			[
				'Call to function FunctionCallWithoutSideEffectPipe\pureFunc() on a separate line has no effect.',
				28,
			],
		]);
	}

}
