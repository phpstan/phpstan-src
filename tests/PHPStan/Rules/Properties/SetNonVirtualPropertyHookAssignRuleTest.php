<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<SetNonVirtualPropertyHookAssignRule>
 */
class SetNonVirtualPropertyHookAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SetNonVirtualPropertyHookAssignRule();
	}

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
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
