<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<EnumCaseOutsideEnumRule>
 */
class EnumCaseOutsideEnumRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumCaseOutsideEnumRule();
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14252.php'], [
			[
				'Enum case can only be used in enums.',
				9,
			],
			[
				'Enum case can only be used in enums.',
				14,
			],
			[
				'Enum case can only be used in enums.',
				19,
			],
		]);
	}

}
