<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use Override;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Array shapes are unsealed without bleeding edge.
 *
 * @extends RuleTestCase<NullCoalesceRule>
 */
class NullCoalesceRuleNoBleedingEdgeTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullCoalesceRule(
			new IssetCheck(
				new PropertyDescriptor(),
				true,
				$this->shouldTreatPhpDocTypesAsCertain(),
			),
			self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
			true,
		);
	}

	#[Override]
	public static function getAdditionalConfigFiles(): array
	{
		return [];
	}

	public function testBug15534(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15534.php'], []);
	}

}
