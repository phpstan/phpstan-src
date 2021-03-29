<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UsageOfVoidMatchExpressionRule>
 */
class UsageOfVoidMatchExpressionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UsageOfVoidMatchExpressionRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/void-match.php'], [
			[
				'Result of match expression (void) is used.',
				21,
			],
		]);
	}

}
