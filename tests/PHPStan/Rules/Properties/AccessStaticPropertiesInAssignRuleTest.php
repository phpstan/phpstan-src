<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AccessStaticPropertiesInAssignRule>
 */
class AccessStaticPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new AccessStaticPropertiesInAssignRule(
			new AccessStaticPropertiesRule($broker, new RuleLevelHelper($broker, true, false, true, false), new ClassCaseSensitivityCheck($broker))
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign.php'], [
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AccessStaticPropertyWithDimFetch::$foo.',
				15,
			],
		]);
	}

	public function testRuleExpressionNames(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-static-object.php'], [
			[
				'Access to an undefined static property PropertiesFromArrayIntoStaticObject\Foo::$noop.',
				29,
			],
		]);
	}

}
