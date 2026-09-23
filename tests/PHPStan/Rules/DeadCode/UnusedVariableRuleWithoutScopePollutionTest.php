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

}
