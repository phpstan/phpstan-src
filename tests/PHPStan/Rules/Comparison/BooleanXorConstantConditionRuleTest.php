<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<BooleanXorConstantConditionRule> */
class BooleanXorConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	protected function getRule(): Rule
	{
		return new BooleanXorConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
				),
				$this->treatPhpDocTypesAsCertain,
			),
			$this->treatPhpDocTypesAsCertain,
		);
	}

	public function dataRule(): array
	{
		return [
			[
				true,
				[
					[
						'Left side of xor is always true.',
						7,
					],
					[
						'Right side of xor is always true.',
						12,
					],
					[
						'Left side of xor is always false.',
						17,
					],
					[
						'Right side of xor is always false.',
						22,
					],
					[
						'Left side of xor is always true.',
						31,
						'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
					],
					[
						'Right side of xor is always true.',
						31,
						'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
					],
				],
			],
			[
				false,
				[
					[
						'Left side of xor is always true.',
						7,
					],
					[
						'Right side of xor is always true.',
						12,
					],
					[
						'Left side of xor is always false.',
						17,
					],
					[
						'Right side of xor is always false.',
						22,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param mixed[] $errors
	 */
	public function testRule(bool $phpDocsCertain, array $errors): void
	{
		$this->treatPhpDocTypesAsCertain = $phpDocsCertain;
		$this->analyse([__DIR__ . '/data/boolean-xor.php'], $errors);
	}

}
