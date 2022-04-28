<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrowFunctionReturnNullsafeByRefRule>
 */
class ArrowFunctionReturnNullsafeByRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrowFunctionReturnNullsafeByRefRule(new NullsafeCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/arrow-function-nullsafe-by-ref.php'], [
			[
				'Nullsafe cannot be returned by reference.',
				6,
			],
		]);
	}

}
