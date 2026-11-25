<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<UnusedVariableRule>
 */
class UnusedVariableRuleWithoutScopePollutionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedVariableRule(self::getContainer()->getByType(ExprPrinter::class));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../without-scope-pollution.neon',
			],
		);
	}

	public function testScopePollution(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-scope-pollution.php'], []);
	}

	public function testBug15394(): void
	{
		// a declare body is not a block - it always runs, whatever polluteScopeWithBlock says
		$this->analyse([__DIR__ . '/data/bug-15394.php'], [
			['Value assigned to variable $foo is never read before being overwritten.', 14],
			['Variable $foo is never read.', 24],
		]);
	}

}
