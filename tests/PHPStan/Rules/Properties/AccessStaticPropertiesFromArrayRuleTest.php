<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<AccessStaticPropertiesRule>
 */
class AccessStaticPropertiesFromArrayRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new AccessStaticPropertiesRule(
			$broker,
			new RuleLevelHelper($broker, true, false, true, false),
			new ClassCaseSensitivityCheck($broker)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-static-object.php'], [
			[
				'Cannot access static property $noop on PropertiesFromArrayIntoStaticObject\Foo.',
				29,
			],
		]);
	}

}
