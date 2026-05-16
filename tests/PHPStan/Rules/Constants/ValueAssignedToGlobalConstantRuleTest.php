<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ValueAssignedToGlobalConstantRule>
 */
class ValueAssignedToGlobalConstantRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ValueAssignedToGlobalConstantRule(
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
		$this->analyse([__DIR__ . '/data/value-assigned-to-global-constant.php'], [
			[
				'Configuration defined type for constant BAR_CONSTANT (int|string|null) does not accept value false.',
				3,
			],
		]);
	}

}
