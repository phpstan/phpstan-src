<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
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
		$reflectionProvider = self::createReflectionProvider();
		return new AccessStaticPropertiesInAssignRule(
			new AccessStaticPropertiesRule(
				$reflectionProvider,
				new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
					$reflectionProvider,
					self::getContainer(),
				),
				true,
			),
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
		]);
	}

	public function testRuleAssignOp(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign-op.php'], [
			[
				'Access to an undefined static property AccessStaticProperties\AssignOpNonexistentProperty::$flags.',
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
