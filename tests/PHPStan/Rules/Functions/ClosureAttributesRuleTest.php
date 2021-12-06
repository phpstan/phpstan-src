<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClosureAttributesRule>
 */
class ClosureAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ClosureAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, false),
					new NullsafeCheck(),
					new PhpVersion(80000),
					new UnresolvableTypeHelper(),
					true,
					true,
					true,
					true
				),
				new ClassCaseSensitivityCheck($reflectionProvider, false)
			)
		);
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		if (PHP_VERSION_ID < 70200) {
			$this->markTestSkipped('Test requires PHP 7.2.');
		}

		$this->analyse([__DIR__ . '/data/closure-attributes.php'], [
			[
				'Attribute class ClosureAttributes\Foo does not have the function target.',
				28,
			],
		]);
	}

}
