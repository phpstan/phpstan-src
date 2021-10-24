<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<TooWideClosureReturnTypehintRule>
 */
class TooWideClosureReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWideClosureReturnTypehintRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideClosureReturnType.php'], [
			[
				'Anonymous function never returns null so it can be removed from the return type.',
				20,
			],
		]);
	}

}
