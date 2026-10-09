<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PHPStan\Rules\Constants\OverrideAttributeOnConstantCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<OverridingEnumCaseRule>
 */
class OverridingEnumCaseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverridingEnumCaseRule(
			new OverrideAttributeOnConstantCheck(
				checkMissingOverrideConstantAttribute: true,
				checkMissingOverrideMethodAttribute: true,
			),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/enum-case-override-attr.php'], [
			[
				'Enum case EnumCaseOverrideAttr\Foo::BAR has #[\Override] attribute but does not override any constant.',
				15,
			],
			[
				'Enum case EnumCaseOverrideAttr\Bar::FOO overrides constant EnumCaseOverrideAttr\FooInterface::FOO but is missing the #[\Override] attribute.',
				21,
			],
			[
				'Enum case EnumCaseOverrideAttr\Baz::BAZ has #[\Override] attribute but does not override any constant.',
				26,
			],
		]);
	}

}
