<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ValueAssignedToDefineRule>
 */
class ValueAssignedToDefineRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ValueAssignedToDefineRule(
			self::getContainer()->getByType(ConstantResolver::class),
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
		$this->analyse([__DIR__ . '/data/value-assigned-to-define.php'], [
			[
				'Configuration defined type for constant BAR_CONSTANT (int|string|null) does not accept value false.',
				5,
			],
			[
				'Configuration defined type for constant BAR_CONSTANT (int|string|null) does not accept value int|false.',
				6,
			],
			[
				"Configuration defined type for constant A_NON_EMPTY_STRING (non-empty-string) does not accept value ''.",
				12,
			],
			[
				"Configuration defined type for constant A_NON_EMPTY_STRING (non-empty-string) does not accept value string.",
				14,
			],
		]);
	}

}
