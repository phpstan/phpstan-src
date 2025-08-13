<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideFunctionReturnTypehintRule>
 */
class TooWideFunctionReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWideFunctionReturnTypehintRule(new TooWideTypeCheck());
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

}
