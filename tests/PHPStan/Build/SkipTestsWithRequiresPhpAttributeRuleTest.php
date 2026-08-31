<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SkipTestsWithRequiresPhpAttributeRule>
 */
class SkipTestsWithRequiresPhpAttributeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SkipTestsWithRequiresPhpAttributeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/skip-tests-requires-php.php'], [
			[
				'Skip tests with #[RequiresPhp] attribute instead.',
				13,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/skip-tests-requires-php.php', __DIR__ . '/data/skip-tests-requires-php.php.fixed');
	}

}
