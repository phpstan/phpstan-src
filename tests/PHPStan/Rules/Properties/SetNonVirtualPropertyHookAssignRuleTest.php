<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<SetNonVirtualPropertyHookAssignRule>
 */
class SetNonVirtualPropertyHookAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SetNonVirtualPropertyHookAssignRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/set-non-virtual-property-hook-assign.php'], [
			[
				'Set hook for non-virtual property SetNonVirtualPropertyHookAssign\Foo::$k does not assign value to it.',
				24,
			],
			[
				'Set hook for non-virtual property SetNonVirtualPropertyHookAssign\Foo::$k2 does not always assign value to it.',
				34,
			],
		]);
	}

}
