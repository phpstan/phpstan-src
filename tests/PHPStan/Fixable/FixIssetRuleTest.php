<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FixIssetRule>
 */
class FixIssetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FixIssetRule();
	}

	public function testRule(): void
	{
		$this->fix(__DIR__ . '/data/isset.php', __DIR__ . '/data/isset.php.fixed');
	}

	public function testFixInTraitInSameFile(): void
	{
		$this->fix(__DIR__ . '/data/trait/same-file-trait.php', __DIR__ . '/data/trait/same-file-trait.php.fixed');
	}

	public function testFixInTraitUsedByTwoClasses(): void
	{
		// each using class reports the error, both diffs make the same change
		$this->fixOtherFile(
			__DIR__ . '/data/trait/uses-trait.php',
			__DIR__ . '/data/trait/FixableTrait.php',
			__DIR__ . '/data/trait/FixableTrait.php.fixed',
		);
	}

}
