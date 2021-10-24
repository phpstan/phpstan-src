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
			[
				'This throw is overwritten by a different one in the finally block below.',
				62,
			],
			[
				'This throw is overwritten by a different one in the finally block below.',
				64,
			],
			[
				'The overwriting return is on this line.',
				66,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				81,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				83,
			],
			[
				'The overwriting return is on this line.',
				85,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				91,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				93,
			],
			[
				'The overwriting return is on this line.',
				95,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				101,
			],
			[
				'The overwriting return is on this line.',
				103,
			],
			[
				'This throw is overwritten by a different one in the finally block below.',
				122,
			],
			[
				'This throw is overwritten by a different one in the finally block below.',
				124,
			],
			[
				'The overwriting return is on this line.',
				126,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				141,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				143,
			],
			[
				'The overwriting return is on this line.',
				145,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				151,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				153,
			],
			[
				'The overwriting return is on this line.',
				155,
			],
			[
				'This exit point is overwritten by a different one in the finally block below.',
				161,
			],
			[
				'The overwriting return is on this line.',
				163,
			],
		]);
	}

}
