<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-extends.php'], [
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
		]);
	}

}
