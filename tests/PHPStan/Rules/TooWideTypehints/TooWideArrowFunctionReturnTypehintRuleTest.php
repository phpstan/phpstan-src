<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideArrowFunctionReturnTypehintRule>
 */
class TooWideArrowFunctionReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWideArrowFunctionReturnTypehintRule(
			new TooWideTypeCheck(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideArrowFunctionReturnType.php'], [
			[
				'Anonymous function never returns null so it can be removed from the return type.',
				14,
			],
		]);
	}

}
