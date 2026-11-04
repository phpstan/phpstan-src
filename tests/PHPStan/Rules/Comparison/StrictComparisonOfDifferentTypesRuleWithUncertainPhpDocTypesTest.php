<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<StrictComparisonOfDifferentTypesRule>
 */
class StrictComparisonOfDifferentTypesRuleWithUncertainPhpDocTypesTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new StrictComparisonOfDifferentTypesRule(
			self::getContainer()->getByType(RicherScopeGetTypeHelper::class),
			self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
			false,
			false,
			true,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../uncertain-phpdoc-types.neon',
			],
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return false;
	}

	public function testBug15403(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15403.php'], []);
	}

}
