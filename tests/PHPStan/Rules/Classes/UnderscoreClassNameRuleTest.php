<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnderscoreClassNameRule>
 */
#[RequiresPhp('>= 8.1.0')]
class UnderscoreClassNameRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80400;

	protected function getRule(): Rule
	{
		return new UnderscoreClassNameRule(new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp84(): void
	{
		$this->phpVersionId = 80300;
		$this->analyse($this->getFiles(), []);
	}

}
