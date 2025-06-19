<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TernaryOperatorConstantConditionRule>
 */
class TernaryOperatorConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new TernaryOperatorConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
				),
				$this->treatPhpDocTypesAsCertain,
			),
			$this->treatPhpDocTypesAsCertain,
			true,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/ternary.php'], [
			[
				'Ternary operator condition is always true.',
				11,
			],
			[
				'Ternary operator condition is always false.',
				15,
			],
			[
				'Ternary operator condition is always false.',
				66,
			],
			[
				'Ternary operator condition is always false.',
				67,
			],
			[
				'Ternary operator condition is always true.',
				70,
			],
			[
				'Ternary operator condition is always true.',
				71,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/ternary-not-phpdoc.php'], [
			[
				'Ternary operator condition is always true.',
				16,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/ternary-not-phpdoc.php'], [
			[
				'Ternary operator condition is always true.',
				16,
			],
			[
				'Ternary operator condition is always true.',
				17,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug7580(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7580.php'], []);
	}

	public function testBug3370(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3370.php'], []);
	}

}
