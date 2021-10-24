<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<CallToFunctionStatementWithoutSideEffectsRule>
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

}
