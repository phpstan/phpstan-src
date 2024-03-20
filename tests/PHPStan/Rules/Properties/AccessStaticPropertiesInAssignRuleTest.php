<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessStaticPropertiesInAssignRule>
 */
class AccessStaticPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessStaticPropertiesInAssignRule(
			new AccessStaticPropertiesRule(
				$reflectionProvider,
				new RuleLevelHelper($reflectionProvider, true, false, true, false, false, true, false),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
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
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}
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
