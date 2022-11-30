<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithoutSideEffectsRule>
 */
class CallToFunctionStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithoutSideEffectsRule($this->createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-call-statement-no-side-effects.php'], [
			[
				'Call to function sprintf() on a separate line has no effect.',
				13,
			],
			[
				'Call to function file_get_contents() on a separate line has no effect.',
				14,
			],
			[
				'Call to function file_get_contents() on a separate line has no effect.',
				22,
			],
		]);

		if (PHP_VERSION_ID >= 80000) {
			$this->analyse([__DIR__ . '/data/function-call-statement-no-side-effects-8.0.php'], [
				[
					'Call to function file_get_contents() on a separate line has no effect.',
					12,
				],
				[
					'Call to function file_get_contents() on a separate line has no effect.',
					13,
				],
				[
					'Call to function file_get_contents() on a separate line has no effect.',
					14,
				],
			]);
		}
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
				'Call to function FunctionCallStatementNoSideEffectsPhpDoc\pureAndThrowsVoid() on a separate line has no effect.',
				11,
			],
		]);
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

}
