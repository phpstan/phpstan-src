<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodAnnotationReturnTypeClassDoesNotExistRule>
 */
class MethodAnnotationReturnTypeClassDoesNotExistRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new MethodAnnotationReturnTypeClassDoesNotExistRule(
			$reflectionProvider,
			new ClassCaseSensitivityCheck($reflectionProvider, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-tag-return-class-does-not-exist.php'], [
			[
				'PHPDoc tag @method missing() has invalid return type "MethodTagReturnClassDoesNotExist\IDoNotExist", class does not exist',
				17,
			],
			[
				'PHPDoc tag @method baz() has invalid return type "MethodTagReturnClassDoesNotExist\IDoNotExist", class does not exist',
				17,
			],
			[
				'PHPDoc tag @method zab() has invalid return type "MethodTagReturnClassDoesNotExist\IDoNotExist", class does not exist',
				17,
			],
			[
				'Class PHPStan\Rules\PhpDoc\MethodAnnotationReturnTypeClassDoesNotExistRule referenced with incorrect case: PHPStan\Rules\PhpDoc\methodannotationreturntypeclassdoesnotexistrule.',
				17,
			],
		]);
	}

}
