<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ContinueBreakInLoopRule>
 */
class ContinueBreakInLoopRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ContinueBreakInLoopRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/continue-break.php'], [
			[
				'Keyword break used outside of a loop or a switch statement.',
				67,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				69,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				77,
			],
			[
				'Keyword continue used outside of a loop or a switch statement.',
				79,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				87,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				95,
			],
		]);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/continue-break-property-hook.php'], [
			[
				'Keyword break used outside of a loop or a switch statement.',
				13,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				15,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				24,
			],
			[
				'Keyword continue used outside of a loop or a switch statement.',
				26,
			],
			[
				'Keyword break used outside of a loop or a switch statement.',
				35,
			],
		]);
	}

}
