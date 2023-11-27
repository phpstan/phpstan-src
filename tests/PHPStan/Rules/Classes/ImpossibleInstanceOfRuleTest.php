<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ImpossibleInstanceOfRule>
 */
class ImpossibleInstanceOfRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueInstanceOf;

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		return new ImpossibleInstanceOfRule($this->checkAlwaysTrueInstanceOf, $this->treatPhpDocTypesAsCertain, $this->reportAlwaysTrueInLastCondition);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testInstanceof(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse(
			[__DIR__ . '/data/impossible-instanceof.php'],
			[
				[
					'Instanceof between ImpossibleInstanceOf\Lorem and ImpossibleInstanceOf\Lorem will always evaluate to true.',
					59,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Ipsum and ImpossibleInstanceOf\Lorem will always evaluate to true.',
					65,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Ipsum and ImpossibleInstanceOf\Ipsum will always evaluate to true.',
					68,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					71,
				],
				[
					'Instanceof between ImpossibleInstanceOf\FooImpl and ImpossibleInstanceOf\Foo will always evaluate to true.',
					74,
				],
				[
					'Instanceof between ImpossibleInstanceOf\BarChild and ImpossibleInstanceOf\Bar will always evaluate to true.',
					77,
				],
				[
					'Instanceof between string and ImpossibleInstanceOf\Foo will always evaluate to false.',
					94,
				],
				[
					'Instanceof between string and \'str\' will always evaluate to false.',
					98,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					107,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					119,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					124,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					137,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					142,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					155,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					160,
				],
				[
					'Instanceof between callable and ImpossibleInstanceOf\FinalClassWithoutInvoke will always evaluate to false.',
					204,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Dolor will always evaluate to true.',
					226,
					$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					228,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					232,
					$tipText,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Bar will always evaluate to true.',
					232,
					$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Foo will always evaluate to false.',
					234,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					238,
					//$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Bar will always evaluate to false.',
					240,
					//$tipText,
				],
				[
					'Instanceof between object and Exception will always evaluate to false.',
					303,
				],
				[
					'Instanceof between object and InvalidArgumentException will always evaluate to false.',
					307,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarChild will always evaluate to false.',
					318,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarGrandChild will always evaluate to false.',
					322,
				],
				/*[
					'Instanceof between mixed and int results in an error.',
					353,
				],
				[
					'Instanceof between mixed and ImpossibleInstanceOf\InvalidTypeTest|int results in an error.',
					362,
				],*/
				[
					'Instanceof between ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					388,
					$tipText,
				],
				[
					'Instanceof between T of Exception and Error will always evaluate to false.',
					404,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and DateTimeInterface will always evaluate to false.',
					418,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and class-string<DateTimeInterface> will always evaluate to false.',
					419,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and \'DateTimeInterface\' will always evaluate to false.',
					432,
					$tipText,
				],
				[
					'Instanceof between DateTimeInterface and \'DateTimeInterface\' will always evaluate to true.',
					433,
					$tipText,
				],
			],
		);
	}

	public function testInstanceofWithoutAlwaysTrue(): void
	{
		$this->checkAlwaysTrueInstanceOf = false;
		$this->treatPhpDocTypesAsCertain = true;

		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse(
			[__DIR__ . '/data/impossible-instanceof.php'],
			[
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					71,
				],
				[
					'Instanceof between string and ImpossibleInstanceOf\Foo will always evaluate to false.',
					94,
				],
				[
					'Instanceof between string and \'str\' will always evaluate to false.',
					98,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					119,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					137,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					155,
				],
				[
					'Instanceof between callable and ImpossibleInstanceOf\FinalClassWithoutInvoke will always evaluate to false.',
					204,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					228,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Foo will always evaluate to false.',
					234,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Bar will always evaluate to false.',
					240,
					//$tipText,
				],
				[
					'Instanceof between object and Exception will always evaluate to false.',
					303,
				],
				[
					'Instanceof between object and InvalidArgumentException will always evaluate to false.',
					307,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarChild will always evaluate to false.',
					318,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarGrandChild will always evaluate to false.',
					322,
				],
				/*[
					'Instanceof between mixed and int results in an error.',
					353,
				],
				[
					'Instanceof between mixed and ImpossibleInstanceOf\InvalidTypeTest|int results in an error.',
					362,
				],*/
				[
					'Instanceof between T of Exception and Error will always evaluate to false.',
					404,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and DateTimeInterface will always evaluate to false.',
					418,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and class-string<DateTimeInterface> will always evaluate to false.',
					419,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and \'DateTimeInterface\' will always evaluate to false.',
					432,
					$tipText,
				],
			],
		);
	}

	public function testDoNotReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/impossible-instanceof-not-phpdoc.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				12,
			],
			[
				'Instanceof between stdClass and Exception will always evaluate to false.',
				15,
			],
			[
				'Instanceof between DateTimeInterface and DateTimeInterface will always evaluate to true.',
				27,
			],
			[
				'Instanceof between DateTimeInterface and ImpossibleInstanceofNotPhpDoc\SomeFinalClass will always evaluate to false.',
				30,
			],
		]);
	}

	public function testReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impossible-instanceof-not-phpdoc.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				12,
			],
			[
				'Instanceof between stdClass and Exception will always evaluate to false.',
				15,
			],
			[
				'Instanceof between DateTimeImmutable and DateTimeInterface will always evaluate to true.',
				27,
			],
			[
				'Instanceof between DateTimeImmutable and ImpossibleInstanceofNotPhpDoc\SomeFinalClass will always evaluate to false.',
				30,
			],
			[
				'Instanceof between DateTimeImmutable and DateTimeImmutable will always evaluate to true.',
				33,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Instanceof between DateTimeImmutable and DateTime will always evaluate to false.',
				36,
				//'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug3096(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3096.php'], []);
	}

	public function testBug6213(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6213.php'], []);
	}

	public function testBug5333(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-5333.php'], []);
	}

	public function testBug8042(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('This test needs PHP 8.0');
		}

		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8042.php'], [
			[
				'Instanceof between Bug8042\B and Bug8042\B will always evaluate to true.',
				18,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between Bug8042\B and Bug8042\B will always evaluate to true.',
				26,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testBug7721(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7721.php'], []);
	}

	public function testUnreachableIfBranches(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-if-branches.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				5,
			],
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				13,
			],
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				23,
			],
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				37,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testIfBranchesDoNotReportPhpDoc(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-if-branches-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				26,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				36,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testIfBranchesReportPhpDoc(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-if-branches-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				26,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				36,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				42,
				$tipText,
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				52,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				62,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testUnreachableTernaryElse(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-ternary-else-branch.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				6,
			],
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				9,
			],
		]);
	}

	public function testTernaryElseDoNotReportPhpDoc(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-ternary-else-branch-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				17,
			],
		]);
	}

	public function testTernaryElseReportPhpDoc(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-ternary-else-branch-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				17,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				19,
				$tipText,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				20,
				$tipText,
			],
		]);
	}

	public function testBug4689(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-4689.php'], []);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Instanceof between Exception and Exception will always evaluate to true.',
				21,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Instanceof between Exception and Exception will always evaluate to true.',
				12,
			],
			[
				'Instanceof between Exception and Exception will always evaluate to true.',
				21,
			],
			[
				'Instanceof between DateTime and DateTime will always evaluate to true.',
				34,
			],
		]];
	}

	/**
	 * @dataProvider dataReportAlwaysTrueInLastCondition
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/impossible-instanceof-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function testBug10201(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-10201.php'], [
			[
				'Instanceof between string and Bug10201\Hello will always evaluate to false.',
				13,
			],
		]);
	}

}
