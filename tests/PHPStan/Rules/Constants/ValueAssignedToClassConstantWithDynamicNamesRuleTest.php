<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ValueAssignedToClassConstantRule>
 */
class ValueAssignedToClassConstantWithDynamicNamesRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ValueAssignedToClassConstantRule(
			self::getContainer()->getByType(ConstantResolver::class),
			true,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/value-assigned-dynamic-constant.neon',
		];
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/value-assigned-to-class-constant-dynamic-names.php'], [
			[
				'Configuration defined type for constant ValueAssignedToClassConstantDynamicNames\Foo::BAR (int|string|null) does not accept value false.',
				12,
			],
			[
				'Configuration defined type for constant ValueAssignedToClassConstantDynamicNames\Foo::MAYBE_BAR (int<1, max>) does not accept value int.',
				14,
			],
			[
				"Configuration defined type for constant ValueAssignedToClassConstantDynamicNames\Foo::A_NON_EMPTY_STRING (non-empty-string) does not accept value ''.",
				15,
			],
		]);
	}

}
