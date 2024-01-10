<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RequireImplementsRule>
 */
class RequireImplementsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RequireImplementsRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1');
		}

		$expectedErrors = [
			[
				'Trait IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InValidTraitUse2 does not.',
				47,
			],
			[
				'Trait IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InvalidEnumTraitUse does not.',
				52,
			],
			[
				'Trait IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InValidTraitUse does not.',
				56,
			],
			[
				'Trait IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-implements.php:117 does not.',
				117,
			],
			[
				'Trait IncompatibleRequireImplements\InvalidTrait1 requires using class to implement IncompatibleRequireImplements\SomeTrait, but IncompatibleRequireImplements\InvalidTraitUse1 does not.',
				125,
			],
			[
				'Trait IncompatibleRequireImplements\InvalidTrait2 requires using class to implement IncompatibleRequireImplements\SomeEnum, but IncompatibleRequireImplements\InvalidTraitUse2 does not.',
				129,
			],
			[
				'Trait IncompatibleRequireImplements\InvalidTrait3 requires using class to implement IncompatibleRequireImplements\TypeDoesNotExist, but IncompatibleRequireImplements\InvalidTraitUse3 does not.',
				133,
			],
			[
				'Trait IncompatibleRequireImplements\InvalidTrait4 requires using class to implement IncompatibleRequireImplements\SomeClass<T>, but IncompatibleRequireImplements\InvalidTraitUse4 does not.',
				137,
			],
			[
				'Trait IncompatibleRequireImplements\ValidPsalmTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface2, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-implements.php:164 does not.',
				164,
			],
			[
				'Trait IncompatibleRequireImplements\ValidPsalmTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-implements.php:168 does not.',
				168,
			],
			[
				'Trait IncompatibleRequireImplements\ValidPsalmTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface2, but class@anonymous/tests/PHPStan/Rules/PhpDoc/data/incompatible-require-implements.php:168 does not.',
				168,
			],
		];

		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-implements.php'], $expectedErrors);
	}

}
