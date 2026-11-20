<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OrdSingleByteRule>
 */
class OrdSingleByteRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OrdSingleByteRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$message = 'Providing a string that is not one byte long to ord() is deprecated in PHP 8.5.';
		$tip = 'Use ord($str[0]) instead.';
		$this->analyse([__DIR__ . '/data/ord-single-byte.php'], [
			[$message, 13, $tip],
			[$message, 14, $tip],
			[$message, 15, $tip],
			[$message, 17, $tip],
			['Providing an empty string to ord() is deprecated in PHP 8.5.', 18],
		]);
	}

	public function testCoercion(): void
	{
		$this->analyse([__DIR__ . '/data/ord-single-byte-coercion.php'], [
			['Providing a string that is not one byte long to ord() is deprecated in PHP 8.5.', 7, 'Use ord($str[0]) instead.'],
			['Providing an empty string to ord() is deprecated in PHP 8.5.', 10],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/ord-single-byte-php-version.php'], [
			['Providing a string that is not one byte long to ord() is deprecated in PHP 8.5.', 10, 'Use ord($str[0]) instead.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/ord-single-byte.php', __DIR__ . '/data/ord-single-byte.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80400-80500.neon',
		];
	}

}
