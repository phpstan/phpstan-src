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
				'This throw is overwritten by a different one in the finally block below.',
				8,
			],
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

	public function testBug5627(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5627.php'], [
			[
				'This throw is overwritten by a different one in the finally block below.',
				10,
			],
			[
				'This throw is overwritten by a different one in the finally block below.',
				12,
			],
			[
				'The overwriting return is on this line.',
				14,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				29,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				31,
			],
			[
				'The overwriting return is on this line.',
				33,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				39,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				41,
			],
			[
				'The overwriting return is on this line.',
				43,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				49,
			],
			[
				'The overwriting return is on this line.',
				51,
			],
		]);
	}

}
