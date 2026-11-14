<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PowerOfZeroWithNegativeExponentRule>
 */
#[RequiresPhp('>= 8.0.0')]
class PowerOfZeroWithNegativeExponentRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PowerOfZeroWithNegativeExponentRule(self::createReflectionProvider());
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

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/power-of-zero-with-negative-exponent-php-version.php'], [
			['Power of base 0 and negative exponent is deprecated in PHP 8.4.', 10, 'Use fpow() to get the IEEE 754 result.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/power-of-zero-with-negative-exponent.php', __DIR__ . '/data/power-of-zero-with-negative-exponent.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80300-80400.neon',
		];
	}

}
