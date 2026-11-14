<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameFunctionRule>
 */
class RenameFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RenameFunctionRule();
	}

	public function testFixesDoNotChangeTheAnalysedNodes(): void
	{
		$this->analyse([__DIR__ . '/data/rename-function.php'], [
			['Renaming function.', 5],
			['Renaming function.', 10],
			['Renaming function.', 15],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/rename-function.php', __DIR__ . '/data/rename-function.php.fixed');
	}

}
