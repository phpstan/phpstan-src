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
				'IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InValidTraitUse2 does not.',
				47,
			],
			[
				'IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InvalidEnumTraitUse does not.',
				52,
			],
			[
				'IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but IncompatibleRequireImplements\InValidTraitUse does not.',
				56,
			],
			[
				'IncompatibleRequireImplements\ValidTrait requires using class to implement IncompatibleRequireImplements\RequiredInterface, but AnonymousClassafc9baecb6bea09a151e986ec457a7f5 does not.',
				117,
			],
			[
				'IncompatibleRequireImplements\InvalidTrait1 requires using class to implement IncompatibleRequireImplements\SomeTrait, but IncompatibleRequireImplements\InvalidTraitUse1 does not.',
				125,
			],
			[
				'IncompatibleRequireImplements\InvalidTrait2 requires using class to implement IncompatibleRequireImplements\SomeEnum, but IncompatibleRequireImplements\InvalidTraitUse2 does not.',
				129,
			],
			[
				'IncompatibleRequireImplements\InvalidTrait3 requires using class to implement IncompatibleRequireImplements\TypeDoesNotExist, but IncompatibleRequireImplements\InvalidTraitUse3 does not.',
				133,
			],
			[
				'IncompatibleRequireImplements\InvalidTrait4 requires using class to implement IncompatibleRequireImplements\SomeClass<T>, but IncompatibleRequireImplements\InvalidTraitUse4 does not.',
				137,
			],
		];

		$this->analyse([__DIR__ . '/../PhpDoc/data/incompatible-require-implements.php'], $expectedErrors);
	}

}
