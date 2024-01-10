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
				'Trait IncompatibleRequireExtends\ValidTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-extends.php:146 does not.',
				146,
			],
			[
				'Trait IncompatibleRequireExtends\ValidPsalmTrait requires using class to extend IncompatibleRequireExtends\SomeClass, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-extends.php:163 does not.',
				163,
			],
		];

		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-extends.php'], $expectedErrors);
	}

	public function testExtendedInterfaceBug(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10302-extended-interface.php'], [
			[
				'Interface Bug10302ExtendedInterface\BatchAware requires implementing class to extend Bug10302ExtendedInterface\Model, but Bug10302ExtendedInterface\AnotherModel does not.',
				34,
			],
		]);
	}

	public function testExtendedTraitBug(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10302-extended-trait.php'], [
			[
				'Trait Bug10302ExtendedTrait\Foo requires using class to extend Bug10302ExtendedTrait\Father, but Bug10302ExtendedTrait\Baz does not.',
				21,
			],
		]);
	}

}
