<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<DefaultValueTypesAssignedToPropertiesRule>
 */
class DefaultValueTypesAssignedToPropertiesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DefaultValueTypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false));
	}

	public function testDefaultValueTypesAssignedToProperties(): void
	{
		$this->analyse([__DIR__ . '/data/properties-assigned-default-value-types.php'], [
			[
				'Property PropertiesAssignedDefaultValuesTypes\Foo::$stringPropertyWithWrongDefaultValue (string) does not accept default value of type int.',
				15,
			],
			[
				'Static property PropertiesAssignedDefaultValuesTypes\Foo::$staticStringPropertyWithWrongDefaultValue (string) does not accept default value of type int.',
				18,
			],
			[
				'Static property PropertiesAssignedDefaultValuesTypes\Foo::$windowsNtVersions (array<string, string>) does not accept default value of type array<int|string, string>.',
				24,
			],
		]);
	}

	public function testDefaultValueForNativePropertyType(): void
	{
		$this->analyse([__DIR__ . '/data/default-value-for-native-property-type.php'], [
			[
				'Property DefaultValueForNativePropertyType\Foo::$foo (DateTime) does not accept default value of type null.',
				8,
			],
		]);
	}

	public function testBug5607(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5607.php'], [
			[
				'Property Bug5607\Cl::$u (Bug5607\A|null) does not accept default value of type array<int|string, string>.',
				10,
			],
		]);
	}

	public function testBug7933(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/bug-7933.php'], []);
	}

	public function testBug10987(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10987.php'], []);
	}

}
