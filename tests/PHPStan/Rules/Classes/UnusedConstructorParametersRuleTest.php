<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\UnusedFunctionParametersCheck;

/**
 * @extends \PHPStan\Testing\RuleTestCase<UnusedConstructorParametersRule>
 */
class UnusedConstructorParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new UnusedConstructorParametersRule(new UnusedFunctionParametersCheck());
	}

	public function testUnusedConstructorParameters(): void
	{
		$this->analyse([__DIR__ . '/data/unused-constructor-parameters.php'], [
			[
				'Constructor of class UnusedConstructorParameters\Foo has an unused parameter $unusedParameter.',
				11,
			],
			[
				'Constructor of class UnusedConstructorParameters\Foo has an unused parameter $anotherUnusedParameter.',
				11,
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/unused-constructor-parameters-promoted-properties.php'], []);
	}

}
