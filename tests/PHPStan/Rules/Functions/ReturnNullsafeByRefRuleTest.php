<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReturnNullsafeByRefRule>
 */
class ReturnNullsafeByRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnNullsafeByRefRule(new NullsafeCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/return-null-safe-by-ref.php'], [
			[
				'Nullsafe cannot be returned by reference.',
				15,
			],
			[
				'Nullsafe cannot be returned by reference.',
				25,
			],
			[
				'Nullsafe cannot be returned by reference.',
				36,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/return-null-safe-by-ref-property-hooks.php'], [
			[
				'Nullsafe cannot be returned by reference.',
				13,
			],
		]);
	}

}
