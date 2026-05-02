<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameVariableFixRule>
 */
final class PerFileBatchFixerTraitBaselineVetoTest extends RuleTestCase
{

	#[Override]
	protected function getRule(): Rule
	{
		return new RenameVariableFixRule();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/ignore-aware-trait-baseline.neon'];
	}

	public function testTraitWithBaselinedConsumerErrorIsNotFixed(): void
	{
		$this->fix(
			__DIR__ . '/data/ignore-aware-trait-baseline.php',
			__DIR__ . '/data/ignore-aware-trait-baseline.php.fixed',
		);
	}

}
