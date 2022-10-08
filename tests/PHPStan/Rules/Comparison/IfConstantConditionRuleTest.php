<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IfConstantConditionRule>
 */
class IfConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new IfConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
				),
			),
			$this->treatPhpDocTypesAsCertain,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		require_once __DIR__ . '/data/function-definition.php';
		$this->analyse([__DIR__ . '/data/if-condition.php'], [
			[
				'If condition is always true.',
				40,
			],
			[
				'If condition is always false.',
				45,
			],
			[
				'If condition is always true.',
				96,
			],
			[
				'If condition is always true.',
				110,
			],
			[
				'If condition is always true.',
				113,
			],
			[
				'If condition is always true.',
				127,
			],
			[
				'If condition is always true.',
				287,
			],
			[
				'If condition is always false.',
				291,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
			[
				'If condition is always true.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug4043(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4043.php'], [
			[
				'If condition is always false.',
				43,
			],
			[
				'If condition is always true.',
				50,
			],
		]);
	}

	public function testBug5370(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5370.php'], []);
	}

}
