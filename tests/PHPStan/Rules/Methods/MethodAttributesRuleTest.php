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
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MethodAttributesRule>
 */
class MethodAttributesRuleTest extends RuleTestCase
{

	/** @var int */
	private $phpVersion;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new MethodAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, false),
					new NullsafeCheck(),
					new PhpVersion($this->phpVersion),
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

		$this->phpVersion = 80000;

		$this->analyse([__DIR__ . '/data/method-attributes.php'], [
			[
				'Attribute class MethodAttributes\Foo does not have the method target.',
				26,
			],
		]);
	}

	public function testBug5898(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		if (PHP_VERSION_ID < 70200) {
			$this->markTestSkipped('Test requires PHP 7.2.');
		}

		$this->phpVersion = 70400;
		$this->analyse([__DIR__ . '/data/bug-5898.php'], []);
	}

}
