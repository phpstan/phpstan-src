<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RequireExtendsDefinitionClassRule>
 */
class RequireExtendsDefinitionClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new RequireExtendsDefinitionClassRule(
			new RequireExtendsCheck(
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				true,
				true,
			),
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-require-extends.php'], [
			[
				'PHPDoc tag @phpstan-require-extends cannot contain non-class type IncompatibleRequireExtends\SomeTrait.',
				8,
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain an interface IncompatibleRequireExtends\SomeInterface, expected a class.',
				13,
				'If you meant an interface, use @phpstan-require-implements instead.',
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain non-class type IncompatibleRequireExtends\SomeEnum.',
				18,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\TypeDoesNotExist.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-require-extends contains non-object type int.',
				34,
			],
			[
				'PHPDoc tag @phpstan-require-extends is only valid on trait or interface.',
				39,
			],
			[
				'PHPDoc tag @phpstan-require-extends is only valid on trait or interface.',
				44,
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain final class IncompatibleRequireExtends\SomeFinalClass.',
				121,
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain an interface IncompatibleRequireExtends\UnresolvableExtendsInterface, expected a class.',
				135,
				'If you meant an interface, use @phpstan-require-implements instead.',
			],
			[
				'PHPDoc tag @phpstan-require-extends can only be used once.',
				178,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\NonExistentClass.',
				183,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\SomeClass.',
				183,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
