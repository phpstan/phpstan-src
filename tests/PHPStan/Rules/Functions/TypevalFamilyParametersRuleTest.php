<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TypevalFamilyParametersRule>
 */
class TypevalFamilyParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new TypevalFamilyParametersRule(new RuleLevelHelper($broker, true, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/typeval.php'], [
			[
				'Parameter #1 $value of function intval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.',
				10,
			],
			[
				'Parameter #1 $value of function floatval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.',
				13,
			],
			[
				'Parameter #1 $value of function doubleval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.',
				16,
			],
		]);
	}

}
