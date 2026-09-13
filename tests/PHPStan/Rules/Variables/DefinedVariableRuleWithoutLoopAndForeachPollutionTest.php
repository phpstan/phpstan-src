<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_merge;

/**
 * @extends RuleTestCase<DefinedVariableRule>
 */
class DefinedVariableRuleWithoutLoopAndForeachPollutionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DefinedVariableRule(
			true,
			true,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../without-loop-pollution.neon',
				__DIR__ . '/../without-foreach-pollution.neon',
			],
		);
	}

	public static function dataForeachPolluteScopeWithAlwaysIterableForeach(): array
	{
		return [
			[
				false,
				[
					[
						'Undefined variable: $key',
						8,
					],
					[
						'Undefined variable: $val',
						9,
					],
					[
						'Undefined variable: $test',
						10,
					],
					[
						'Variable $key might not be defined.',
						19,
					],
					[
						'Variable $val might not be defined.',
						20,
					],
					[
						'Variable $test might not be defined.',
						21,
					],
					[
						'Variable $key might not be defined.',
						32,
					],
					[
						'Variable $val might not be defined.',
						33,
					],
					[
						'Variable $test might not be defined.',
						34,
					],
					[
						'Variable $key might not be defined.',
						47,
					],
					[
						'Variable $test might not be defined.',
						48,
					],
					[
						'Variable $key might not be defined.',
						61,
					],
					[
						'Variable $test might not be defined.',
						62,
					],
					[
						'Variable $key might not be defined.',
						75,
					],
					[
						'Variable $test might not be defined.',
						76,
					],
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataForeachPolluteScopeWithAlwaysIterableForeach')]
	public function testForeachPolluteScopeWithAlwaysIterableForeach(bool $polluteScopeWithAlwaysIterableForeach, array $errors): void
	{
		$this->analyse([__DIR__ . '/data/foreach-always-iterable.php'], $errors);
	}

	public function testBug8467c(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8467c.php'], [
			[
				'Variable $v might not be defined.',
				16,
			],
			[
				'Variable $v might not be defined.',
				18,
			],
		]);
	}

}
