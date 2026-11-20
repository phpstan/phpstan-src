<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnderscoreClassNameRule>
 */
#[RequiresPhp('>= 8.1.0')]
class UnderscoreClassNameRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnderscoreClassNameRule();
	}

	/**
	 * @return list<string>
	 */
	private function getFiles(): array
	{
		return [
			__DIR__ . '/data/underscore-class-name/class.php',
			__DIR__ . '/data/underscore-class-name/interface.php',
			__DIR__ . '/data/underscore-class-name/trait.php',
			__DIR__ . '/data/underscore-class-name/enum.php',
			__DIR__ . '/data/underscore-class-name/anonymous.php',
		];
	}

	public function testRule(): void
	{
		$this->analyse($this->getFiles(), [
			['Using "_" as a class name is deprecated in PHP 8.4.', 5],
			['Using "_" as an interface name is deprecated in PHP 8.4.', 5],
			['Using "_" as a trait name is deprecated in PHP 8.4.', 5],
			['Using "_" as an enum name is deprecated in PHP 8.4.', 5],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/underscore-class-name/php-version.php'], [
			['Using "_" as a class name is deprecated in PHP 8.4.', 13],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80300-80400.neon',
		];
	}

}
