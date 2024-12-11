<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidPromotedPropertiesRule>
 */
class InvalidPromotedPropertiesRuleTest extends RuleTestCase
{

	private int $phpVersion;

	protected function getRule(): Rule
	{
		return new InvalidPromotedPropertiesRule(new PhpVersion($this->phpVersion));
	}

	public function testNotSupportedOnPhp7(): void
	{
		$this->phpVersion = 70400;
		$this->analyse([__DIR__ . '/data/invalid-promoted-properties.php'], [
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				8,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				10,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				17,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				21,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				23,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				31,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				38,
			],
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				45,
			],
		]);
	}

	public function testSupportedOnPhp8(): void
	{
		$this->phpVersion = 80000;
		$this->analyse([__DIR__ . '/data/invalid-promoted-properties.php'], [
			[
				'Promoted properties can be in constructor only.',
				10,
			],
			[
				'Promoted properties can be in constructor only.',
				17,
			],
			[
				'Promoted properties can be in constructor only.',
				21,
			],
			[
				'Promoted properties can be in constructor only.',
				23,
			],
			[
				'Promoted properties are not allowed in abstract constructors.',
				31,
			],
			[
				'Promoted properties are not allowed in abstract constructors.',
				38,
			],
			[
				'Promoted property parameter $i can not be variadic.',
				45,
			],
		]);
	}

	public function testBug9577(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->phpVersion = 80100;
		$this->analyse([__DIR__ . '/data/bug-9577.php'], []);
	}

	public function testHooks(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->phpVersion = 80100;
		$this->analyse([__DIR__ . '/data/invalid-hooked-properties.php'], [
			[
				'Promoted properties can be in constructor only.',
				9,
			],
		]);
	}

}
