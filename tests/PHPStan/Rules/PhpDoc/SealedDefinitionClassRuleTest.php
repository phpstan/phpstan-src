<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\DependencyInjection\BleedingEdgeToggle;
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
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				46,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testSubtypes(): void
	{
		$this->analyse([__DIR__ . '/data/sealed-subtypes.php'], [
			[
				'PHPDoc tag @phpstan-sealed contains final type SealedSubtypes\\__YEnumInvalid that is not subtype of SealedSubtypes\\__EnumError.',
				23,
			],
			[
				'PHPDoc tag @phpstan-sealed contains final type SealedSubtypes\\__YClassInvalid that is not subtype of SealedSubtypes\\__ClassError.',
				65,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testNonFinalSubtypes(): void
	{
		$this->analyse([__DIR__ . '/data/sealed-non-final-subtypes.php'], [
			[
				'PHPDoc tag @phpstan-sealed contains final type SealedNonFinalSubtypes\\InvalidZ that is not subtype of SealedNonFinalSubtypes\\InvalidSealed.',
				8,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testFinalSubtypesAreNotCheckedWithoutBleedingEdge(): void
	{
		BleedingEdgeToggle::withBleedingEdge(false, function (): void {
			$this->analyse([__DIR__ . '/data/sealed-non-final-subtypes.php'], []);
		});
	}

}
