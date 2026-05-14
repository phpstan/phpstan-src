<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameVariableFixRule>
 */
final class PerFileBatchFixerIgnoreAwarenessBaselineNoMatchTest extends RuleTestCase
{

	#[Override]
	protected function getRule(): Rule
	{
		return new RenameVariableFixRule();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/ignore-aware-baseline-no-match.neon'];
	}

	public function testBaselineDifferentIdentifierLeavesUnrelatedFixesApplied(): void
	{
		$this->fix(
			__DIR__ . '/data/ignore-aware-baseline-no-match.php',
			__DIR__ . '/data/ignore-aware-baseline-no-match.php.fixed',
		);
	}

}
