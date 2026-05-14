<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameVariableFixRule>
 */
final class PerFileBatchFixerTest extends RuleTestCase
{

	#[Override]
	protected function getRule(): Rule
	{
		return new RenameVariableFixRule();
	}

	public function testMultipleSameLineFixesAllApply(): void
	{
		$this->fix(__DIR__ . '/data/same-line.php', __DIR__ . '/data/same-line.php.fixed');
	}

}
