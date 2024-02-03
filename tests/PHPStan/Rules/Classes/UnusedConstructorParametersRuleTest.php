<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedFunctionParametersCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedConstructorParametersRule>
 */
class UnusedConstructorParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
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
		$this->analyse([__DIR__ . '/data/unused-constructor-parameters-promoted-properties.php'], []);
	}

	public function testBug1917(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1917.php'], []);
	}

}
