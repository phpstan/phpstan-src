<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function sprintf;
use const PHP_VERSION_ID;

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
		$paramName = '$value';
		if (PHP_VERSION_ID < 80000) {
			$paramName = '$var';
		}
		$this->analyse([__DIR__ . '/data/typeval.php'], [
			[
				sprintf('Parameter #1 %s of function intval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.', $paramName),
				10,
			],
			[
				sprintf('Parameter #1 %s of function floatval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.', $paramName),
				13,
			],
			[
				sprintf('Parameter #1 %s of function doubleval does not accept object, class@anonymous/tests/PHPStan/Rules/Functions/data/typeval.php:3 given.', $paramName),
				16,
			],
		]);
	}

}
