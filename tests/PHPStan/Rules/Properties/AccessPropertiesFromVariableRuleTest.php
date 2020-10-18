<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AccessPropertiesInAssignRule>
 */
class AccessPropertiesFromVariableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new AccessPropertiesInAssignRule(
			new AccessPropertiesRule($broker, new RuleLevelHelper($broker, true, false, true, false), true)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-variable-into-object.php'], [
			[
				'Access to an undefined property PropertiesFromVariableIntoObject\Foo::$noop.',
				26,
			],
		]);
	}

}
