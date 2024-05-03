<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RedeclareReadOnlyProperty>
 */
class RedeclareReadonlyPropertyRuleTest extends RuleTestCase
{

	private int $phpVersion;

	protected function getRule(): Rule
	{
		return new RedeclareReadOnlyProperty(new PhpVersion($this->phpVersion));
	}

	public function testNotSupportedOnPhp8(): void
	{
		$this->phpVersion = 80000;
		$this->analyse([__DIR__ . '/data/redeclare-readonly-property.php'], []);
	}

	public function testSupportedOnPhp81(): void
	{
		$this->phpVersion = 80100;
		$this->analyse([__DIR__ . '/data/redeclare-readonly-property.php'], [
			[
				'Readonly property RedeclareReadonlyProperty\B1::$myProp cannot be redeclared, because you call the parent constructor.',
				15,
			],
			[
				'Readonly property RedeclareReadonlyProperty\B4::$nonPromotedProp cannot be redeclared, because you call the parent constructor.',
				37,
			],
			[
				'Readonly property RedeclareReadonlyProperty\B5::$myProp cannot be redeclared, because you call the parent constructor.',
				47,
			],
			[
				'Readonly property RedeclareReadonlyProperty\B7::$myProp cannot be redeclared, because you call the parent constructor.',
				64,
			],
			[
				'Readonly property AnonymousClassd46c4a7fade8fbe381fe74954f5c073f::$nonPromotedProp cannot be redeclared, because you call the parent constructor.',
				118,
			],
		]);
	}

}
