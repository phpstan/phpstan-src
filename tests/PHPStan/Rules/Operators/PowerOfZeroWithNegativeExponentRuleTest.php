<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PowerOfZeroWithNegativeExponentRule>
 */
#[RequiresPhp('>= 8.0.0')]
class PowerOfZeroWithNegativeExponentRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80400;

	protected function getRule(): Rule
	{
		return new PowerOfZeroWithNegativeExponentRule(self::createReflectionProvider(), new PhpVersion($this->phpVersionId));
	}

	public function testRule(): void
	{
		$message = 'Power of base 0 and negative exponent is deprecated in PHP 8.4.';
		$tip = 'Use fpow() to get the IEEE 754 result.';
		$this->analyse([__DIR__ . '/data/power-of-zero-with-negative-exponent.php'], [
			[$message, 13, $tip],
			[$message, 14, $tip],
			[$message, 15, $tip],
			[$message, 16, $tip],
			[$message, 17, $tip],
			[$message, 18, $tip],
			[$message, 19, $tip],
			[$message, 21, $tip],
		]);
	}

	public function testBeforePhp84(): void
	{
		$this->phpVersionId = 80300;
		$this->analyse([__DIR__ . '/data/power-of-zero-with-negative-exponent.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/power-of-zero-with-negative-exponent.php', __DIR__ . '/data/power-of-zero-with-negative-exponent.php.fixed');
	}

}
