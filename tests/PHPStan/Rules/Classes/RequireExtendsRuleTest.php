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
				'Trait IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InValidTraitUse2 does not.',
				46,
			],
			[
				'Trait IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InValidTraitUse does not.',
				51,
			],
			[
				'Interface IncompatibleRequireExtends\ValidInterface requires implementing class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InvalidInterfaceUse2 does not.',
				56,
			],
			[
				'Interface IncompatibleRequireExtends\ValidInterface requires implementing class to extend IncompatibleRequireExtends\SomeClass, but IncompatibleRequireExtends\InvalidInterfaceUse does not.',
				58,
			],
			[
				'Trait IncompatibleRequireExtends\InvalidTrait requires using class to extend IncompatibleRequireExtends\SomeFinalClass, but IncompatibleRequireExtends\InvalidClass2 does not.',
				128,
			],
			[
				'Trait IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but AnonymousClassa283e4f1ac026ef382e66fd5041afd37 does not.',
				146,
			],
			[
				'Trait IncompatibleRequireExtends\ValidPsalmTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but AnonymousClass5bf4ec413e09e097359ccaeeabd1bb60 does not.',
				163,
			],
		];

		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-extends.php'], $expectedErrors);
	}

}
