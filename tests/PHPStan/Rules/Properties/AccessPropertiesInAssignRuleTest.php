<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessPropertiesInAssignRule>
 */
class AccessPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessPropertiesInAssignRule(
			new AccessPropertiesRule($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, false, true, false, false), true, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-properties-assign.php'], [
			[
				'Access to an undefined property TestAccessPropertiesAssign\AccessPropertyWithDimFetch::$foo.',
				10,
			],
			[
				'Access to an undefined property TestAccessPropertiesAssign\AccessPropertyWithDimFetch::$foo.',
				15,
			],
		]);
	}

	public function testRuleAssignOp(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/access-properties-assign-op.php'], [
			[
				'Access to an undefined property TestAccessProperties\AssignOpNonexistentProperty::$flags.',
				15,
			],
		]);
	}

	public function testRuleExpressionNames(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-variable-into-object.php'], [
			[
				'Access to an undefined property PropertiesFromVariableIntoObject\Foo::$noop.',
				26,
			],
		]);
	}

	public function testRuleExpressionNames2(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-object.php'], [
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				42,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				54,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				69,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				110,
			],
		]);
	}

	public function testBug4492(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4492.php'], []);
	}

}
