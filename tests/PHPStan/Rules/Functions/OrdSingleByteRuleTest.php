<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OrdSingleByteRule>
 */
class OrdSingleByteRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80500;

	protected function getRule(): Rule
	{
		return new OrdSingleByteRule(self::createReflectionProvider(), new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp85(): void
	{
		$this->phpVersionId = 80400;
		$this->analyse([__DIR__ . '/data/ord-single-byte.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/ord-single-byte.php', __DIR__ . '/data/ord-single-byte.php.fixed');
	}

}
