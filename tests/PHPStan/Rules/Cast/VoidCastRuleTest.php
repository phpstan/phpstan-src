<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VoidCastRule>
 */
class VoidCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VoidCastRule();
	}

	public function testPrintRule(): void
	{
		$this->analyse([__DIR__ . '/data/void-cast.php'], [
			[
				'The (void) cast cannot be used within an expression.',
				5,
			],
			[
				'The (void) cast cannot be used within an expression.',
				6,
			],
			[
				'The (void) cast cannot be used within an expression.',
				7,
			],
		]);
	}

}
