<?php declare(strict_types = 1);

namespace PHPStan\Rules\Ternary;

use PHPStan\Php\PhpVersion;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireParenthesesForNestedTernaryRule>
 */
class RequireParenthesesForNestedTernaryRuleTest extends RuleTestCase
{

	/** @var PhpVersion */
	private $phpVersion;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new RequireParenthesesForNestedTernaryRule($this->phpVersion);
	}

	public function testDoNotReportBeforePhp80(): void
	{
		$this->phpVersion = new PhpVersion(70400);
		$this->analyse([__DIR__ . '/data/nested-ternary.php'], []);
	}

	public function testReportOnPhp80(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}
		$this->phpVersion = new PhpVersion(80000);
		$tip = 'See: https://wiki.php.net/rfc/ternary_associativity';
		$this->analyse([__DIR__ . '/data/nested-ternary.php'], [
			[
				'Nested ternary operator needs to have parentheses around it.',
				5,
				$tip,
			],
			[
				'Nested ternary operator needs to have parentheses around it.',
				9,
				$tip,
			],
			[
				'Nested ternary operator needs to have parentheses around it.',
				13,
				$tip,
			],
		]);
	}

	public function testBug(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}
		$this->phpVersion = new PhpVersion(80000);
		$this->analyse([__DIR__ . '/data/require-parentheses-bug.php'], []);
	}

}
