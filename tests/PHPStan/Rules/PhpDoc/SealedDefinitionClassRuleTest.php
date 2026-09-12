<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<SealedDefinitionClassRule>
 */
class SealedDefinitionClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new SealedDefinitionClassRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true, true),
				new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
				$reflectionProvider,
				$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
			),
			true,
			true,
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-sealed.php'], [
			[
				'PHPDoc tag @phpstan-sealed is only valid on class or interface.',
				16,
			],
			[
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				21,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				26,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-sealed type IncompatibleSealed\SomeClass is not subtype of IncompatibleSealed\Valid.',
				31,
			],
			[
				'PHPDoc tag @phpstan-sealed type IncompatibleSealed\SomeClass is not subtype of IncompatibleSealed\ValidInterface.',
				36,
			],
			[
				'PHPDoc tag @phpstan-sealed type IncompatibleSealed\SomeInterface is not subtype of IncompatibleSealed\ValidInterface2.',
				41,
			],
			[
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				46,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-sealed type IncompatibleSealed\SomeClass is not subtype of IncompatibleSealed\InvalidClassWithUnion.',
				46,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testSubtypes(): void
	{
		$this->analyse([__DIR__ . '/data/sealed-subtypes.php'], [
			[
				'PHPDoc tag @phpstan-sealed type SealedSubtypes\\__YEnumInvalid is not subtype of SealedSubtypes\\__EnumError.',
				23,
			],
			[
				'PHPDoc tag @phpstan-sealed type SealedSubtypes\\__YInterfaceInvalid is not subtype of SealedSubtypes\\__InterfaceError.',
				37,
			],
			[
				'PHPDoc tag @phpstan-sealed type SealedSubtypes\\__YAbstractClassInvalid is not subtype of SealedSubtypes\\__AbstractClassError.',
				51,
			],
			[
				'PHPDoc tag @phpstan-sealed type SealedSubtypes\\__YClassInvalid is not subtype of SealedSubtypes\\__ClassError.',
				65,
			],
		]);
	}

}
