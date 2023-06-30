<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnreachableIfBranchesRule>
 */
class UnreachableIfBranchesRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new UnreachableIfBranchesRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
					true,
				),
				$this->treatPhpDocTypesAsCertain,
				true,
			),
			$this->treatPhpDocTypesAsCertain,
			false,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/unreachable-if-branches.php'], [
			[
				'Else branch is unreachable because previous condition is always true.',
				15,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				25,
			],
			[
				'Else branch is unreachable because previous condition is always true.',
				27,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				39,
			],
			[
				'Else branch is unreachable because previous condition is always true.',
				41,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/unreachable-if-branches-not-phpdoc.php'], [
			[
				'Elseif branch is unreachable because previous condition is always true.',
				18,
			],
			[
				'Else branch is unreachable because previous condition is always true.',
				28,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				38,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/unreachable-if-branches-not-phpdoc.php'], [
			[
				'Elseif branch is unreachable because previous condition is always true.',
				18,
			],
			[
				'Else branch is unreachable because previous condition is always true.',
				28,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				38,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				44,
				$tipText,
			],
			[
				'Else branch is unreachable because previous condition is always true.',
				54,
				//$tipText,
			],
			[
				'Elseif branch is unreachable because previous condition is always true.',
				64,
				//$tipText,
			],
		]);
	}

	public function testBug8076(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8076.php'], []);
	}

	public function testBug8562(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8562.php'], []);
	}

	public function testBug7491(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7491.php'], []);
	}

}
