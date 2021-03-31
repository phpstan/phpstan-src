<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OverwrittenExitPointByFinallyRule>
 */
class OverwrittenExitPointByFinallyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverwrittenExitPointByFinallyRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/overwritten-exit-point.php'], [
			[
				'This return is overwritten by a different one in the finally block below.',
				11,
			],
			[
				'This return is overwritten by a different one in the finally block below.',
				13,
			],
			[
				'The overwriting return is on this line.',
				15,
			],
		]);
	}

}
