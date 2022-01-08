<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AccessStaticPropertiesInAssignRule>
 */
class AccessStaticPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessStaticPropertiesInAssignRule(
			new AccessStaticPropertiesRule($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, false, true, false), new ClassCaseSensitivityCheck($reflectionProvider, true)),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign.php'], [
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AccessStaticPropertyWithDimFetch::$foo.',
				10,
			],
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AccessStaticPropertyWithDimFetch::$foo.',
				15,
			],
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AssignOpNonexistentProperty::$flags.',
				30,
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
