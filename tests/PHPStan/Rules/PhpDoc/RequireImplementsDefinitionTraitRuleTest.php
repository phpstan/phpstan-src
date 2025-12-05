<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RequireImplementsDefinitionTraitRule>
 */
class RequireImplementsDefinitionTraitRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new RequireImplementsDefinitionTraitRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			true,
			true,
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$expectedErrors = [
			[
				'PHPDoc tag @phpstan-require-implements cannot contain non-interface type IncompatibleRequireImplements\SomeTrait.',
				8,
			],
			[
				'PHPDoc tag @phpstan-require-implements cannot contain non-interface type IncompatibleRequireImplements\SomeEnum.',
				13,
			],
			[
				'PHPDoc tag @phpstan-require-implements contains unknown class IncompatibleRequireImplements\TypeDoesNotExist.',
				18,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-require-implements cannot contain non-interface type IncompatibleRequireImplements\SomeClass.',
				24,
			],
			[
				'PHPDoc tag @phpstan-require-implements contains non-object type int.',
				29,
			],
			[
				'PHPDoc tag @phpstan-require-implements contains non-object type *NEVER*.',
				34,
			],
			[
				'PHPDoc tag @phpstan-require-implements contains unknown class IncompatibleRequireImplements\TypeDoesNotExist.',
				175,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		];

		$this->analyse([__DIR__ . '/data/incompatible-require-implements.php'], $expectedErrors);
	}

}
