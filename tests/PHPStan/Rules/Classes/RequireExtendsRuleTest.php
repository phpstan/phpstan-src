<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RequireExtendsRule>
 */
class RequireExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RequireExtendsRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1');
		}

		$expectedErrors = [
			[
				'IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InValidTraitUse2 does not.',
				46,
			],
			[
				'IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InValidTraitUse does not.',
				51,
			],
			[
				'IncompatibleRequireExtends\ValidInterface requires implementing class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InvalidInterfaceUse2 does not.',
				56,
			],
			[
				'IncompatibleRequireExtends\ValidInterface requires implementing class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InvalidInterfaceUse does not.',
				58,
			],
			[
				'IncompatibleRequireExtends\InvalidTrait requires using class to extend IncompatibleRequireExtends\SomeFinalClass, but IncompatibleRequireExtends\InvalidClass2 does not.',
				128,
			],
		];

		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-extends.php'], $expectedErrors);
	}

}
