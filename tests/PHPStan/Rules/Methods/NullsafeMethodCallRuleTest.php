<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NullsafeMethodCallRule>
 */
class NullsafeMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafeMethodCallRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-method-call-rule.php'], [
			[
				'Using nullsafe method call on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

}
