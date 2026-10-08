<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Runs without bleedingEdge.neon, where a sealed PHPDoc shape carries no
 * sealedness information and reports isUnsealed() as maybe.
 *
 * @extends RuleTestCase<IssetRule>
 */
class IssetRuleWithoutBleedingEdgeTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IssetRule(
			new IssetCheck(
				new PropertyDescriptor(),
				true,
				true,
			),
			self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
		);
	}

	public function testBug15428(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15428.php'], [
			[
				'Offset \'a\' on array{} in isset() does not exist.',
				15,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [];
	}

}
