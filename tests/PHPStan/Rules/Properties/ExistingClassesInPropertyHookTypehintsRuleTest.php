<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInPropertyHookTypehintsRule>
 */
class ExistingClassesInPropertyHookTypehintsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ExistingClassesInPropertyHookTypehintsRule(
			new FunctionDefinitionCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
				new UnresolvableTypeHelper(),
				new PhpVersion(PHP_VERSION_ID),
				true,
				false,
			),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/existing-classes-property-hooks.php'], [
			[
				'Parameter $v of set hook for property ExistingClassesPropertyHooks\Foo::$i has invalid type ExistingClassesPropertyHooks\Nonexistent.',
				9,
			],
			[
				'Parameter $v of set hook for property ExistingClassesPropertyHooks\Foo::$j has unresolvable native type.',
				15,
			],
			[
				'Get hook for property ExistingClassesPropertyHooks\Foo::$k has invalid return type ExistingClassesPropertyHooks\Undefined.',
				22,
			],
			[
				'Parameter $value of set hook for property ExistingClassesPropertyHooks\Foo::$l has invalid type ExistingClassesPropertyHooks\Undefined.',
				29,
			],
		]);
	}

}
