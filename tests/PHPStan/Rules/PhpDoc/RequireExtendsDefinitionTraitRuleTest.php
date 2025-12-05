<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RequireExtendsDefinitionTraitRule>
 */
class RequireExtendsDefinitionTraitRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new RequireExtendsDefinitionTraitRule(
			$reflectionProvider,
			new RequireExtendsCheck(
				$reflectionProvider,
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
				'PHPDoc tag @phpstan-require-extends cannot contain final class IncompatibleRequireExtends\SomeFinalClass.',
				126,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains non-object type *NEVER*.',
				140,
			],
			[
				'PHPDoc tag @phpstan-require-extends can only be used once.',
				171,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\NonExistentClass.',
				192,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
