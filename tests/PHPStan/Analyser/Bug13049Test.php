<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Generics\PropertyVarianceRule;
use PHPStan\Rules\Generics\VarianceCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PropertyVarianceRule>
 */
class Bug13049Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertyVarianceRule(new VarianceCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13049.php'], []);
	}

}
