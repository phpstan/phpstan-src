<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodAttributesRule>
 */
class MethodAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new MethodAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true),
					new NullsafeCheck(),
					new PhpVersion(80000),
					new UnresolvableTypeHelper(true),
					true,
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

		$this->analyse([__DIR__ . '/data/method-attributes.php'], [
			[
				'Attribute class MethodAttributes\Foo does not have the method target.',
				26,
			],
		]);
	}

}
