<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameUseRule>
 */
class RenameUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RenameUseRule();
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/rename-use.php', __DIR__ . '/data/rename-use.php.fixed');
	}

}
