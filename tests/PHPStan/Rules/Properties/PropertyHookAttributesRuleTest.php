<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyHookAttributesRule>
 */
class PropertyHookAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new PropertyHookAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false),
					new NullsafeCheck(),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					true,
					true,
					true,
					true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, false),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
				true,
			),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/property-hook-attributes.php'], [
			[
				'Attribute class PropertyHookAttributes\Foo does not have the method target.',
				27,
			],
		]);
	}

}
