<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NullsafeMethodCallOnFirstClassCallableRule>
 */
class NullsafeMethodCallOnFirstClassCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafeMethodCallOnFirstClassCallableRule();
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-first-class-callable.php'], [
			[
				'Cannot combine nullsafe operator with Closure creation.',
				20,
			],
			[
				'Cannot combine nullsafe operator with Closure creation.',
				28,
			],
		]);
	}

}
