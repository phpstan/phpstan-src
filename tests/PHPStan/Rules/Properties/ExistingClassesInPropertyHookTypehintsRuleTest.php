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
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInPropertyHookTypehintsRule>
 */
class ExistingClassesInPropertyHookTypehintsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassesInPropertyHookTypehintsRule(
			new FunctionDefinitionCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new UnresolvableTypeHelper(),
				new PhpVersion(PHP_VERSION_ID),
				true,
				false,
			),
		);
	}

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
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
