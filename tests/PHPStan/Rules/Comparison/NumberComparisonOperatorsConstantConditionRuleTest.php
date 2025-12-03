<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NumberComparisonOperatorsConstantConditionRule>
 */
class NumberComparisonOperatorsConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new NumberComparisonOperatorsConstantConditionRule(
			$this->treatPhpDocTypesAsCertain,
			true,
		);
	}

	public function testBug8277(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8277.php'], []);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/number-comparison-operators.php'], [
			[
				'Comparison operation "<=" between int<6, max> and 2 is always false.',
				7,
			],
			[
				'Comparison operation ">" between int<2, 4> and 8 is always false.',
				13,
			],
			[
				'Comparison operation "<" between int<min, 1> and 5 is always true.',
				21,
			],
		]);
	}

	public function testBug2648(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2648-rule.php'], []);
	}

	public function testBug2648Namespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2648-namespace-rule.php'], []);
	}

	public function testBug5161(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

	public function testBug3310(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3310.php'], []);
	}

	public function testBug3264(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3264.php'], []);
	}

	public function testBug5656(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5656.php'], []);
	}

	public function testBug3867(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3867.php'], []);
	}

	public function testIntegerRangeGeneralization(): void
	{
		$this->analyse([__DIR__ . '/data/integer-range-generalization.php'], []);
	}

	public function testBug3153(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3153.php'], []);
	}

	public function testBug5707(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5707.php'], []);
	}

	public function testBug5969(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5969.php'], []);
	}

	public function testBug5295(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5295.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug7052(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7052.php'], [
			[
				'Comparison operation ">" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				16,
			],
			[
				'Comparison operation "<" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				17,
			],
			[
				'Comparison operation ">=" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				18,
			],
			[
				'Comparison operation "<=" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				19,
			],
		]);
	}

	public function testBug7044(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7044.php'], [
			[
				'Comparison operation "<" between 0 and 0 is always false.',
				15,
			],
		]);
	}

	public function testBug3277(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3277.php'], [
			[
				'Comparison operation "<" between 5 and 4 is always false.',
				6,
			],
		]);
	}

	public function testBug6013(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6013.php'], []);
	}

	public function testBug2851(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2851.php'], []);
	}

	public function testBug8643(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8643.php'], []);
	}

	public static function dataTreatPhpDocTypesAsCertain(): iterable
	{
		yield [
			false,
			[],
		];
		yield [
			true,
			[
				[
					'Comparison operation ">=" between int<1, max> and 0 is always true.',
					11,
					'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
				],
				[
					'Comparison operation "<" between int<1, max> and 0 is always false.',
					18,
					'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataTreatPhpDocTypesAsCertain')]
	public function testTreatPhpDocTypesAsCertain(bool $treatPhpDocTypesAsCertain, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/number-comparison-treat.php'], $expectedErrors);
	}

	public function testBug6776(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-6776.php'], []);
	}

	public function testBug7075(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7075.php'], []);
	}

	public function testBug8803(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8803.php'], []);
	}

	public function testBug8938(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8938.php'], []);
	}

	public function testBug5005(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5005.php'], []);
	}

	public function testBug6467(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6467.php'], []);
	}

	public function testBug11484(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-11484.php'], []);
	}

	public function testBug6642(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6642.php'], []);
	}

	public function testBug9850(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-9850.php'], []);
	}

	public function testBug9180(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-9180.php'], []);
	}

	public function testBug12716(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-12716.php'], []);
	}

	public function testBug3387(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3387.php'], []);
	}

	public function testBug13874(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13874.php'], []);
	}

}
