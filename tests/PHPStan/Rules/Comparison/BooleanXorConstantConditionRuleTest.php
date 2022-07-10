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
		return new BooleanXorConstantConditionRule($this->treatPhpDocTypesAsCertain);
	}

	public function dataRule(): array
	{
		return [
			[
				true,
				[
					[
						'Result of xor is always false.',
						5,
					],
					[
						'Result of xor is always false.',
						9,
					],
					[
						'Result of xor is always false.',
						20,
					],
					[
						'Result of xor is always true.',
						24,
					],
					[
						'Result of xor is always false.',
						33,
						'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
					],
				],
			],
			[
				false,
				[
					[
						'Result of xor is always false.',
						5,
					],
					[
						'Result of xor is always false.',
						9,
					],
					[
						'Result of xor is always false.',
						20,
					],
					[
						'Result of xor is always true.',
						24,
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
