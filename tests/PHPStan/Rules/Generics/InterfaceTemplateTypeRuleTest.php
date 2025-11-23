<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InterfaceTemplateTypeRule>
 */
class InterfaceTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new InterfaceTemplateTypeRule(
			new TemplateTypeCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new GenericObjectTypeCheck(),
				$typeAliasResolver,
				true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/interface-template.php'], [
			[
				'PHPDoc tag @template for interface InterfaceTemplateType\Foo cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for interface InterfaceTemplateType\Bar has invalid bound type InterfaceTemplateType\Zazzzu.',
				16,
			],
			[
				'PHPDoc tag @template for interface InterfaceTemplateType\Lorem cannot have existing type alias TypeAlias as its name.',
				33,
			],
			[
				'PHPDoc tag @template for interface InterfaceTemplateType\Ipsum cannot have existing type alias LocalAlias as its name.',
				45,
			],
			[
				'PHPDoc tag @template for interface InterfaceTemplateType\Ipsum cannot have existing type alias ImportedAlias as its name.',
				45,
			],
			[
				'Call-site variance of covariant int in generic type InterfaceTemplateType\Covariant<covariant int> in PHPDoc tag @template U is redundant, template type T of interface InterfaceTemplateType\Covariant has the same variance.',
				74,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of contravariant int in generic type InterfaceTemplateType\Covariant<contravariant int> in PHPDoc tag @template W is in conflict with covariant template type T of interface InterfaceTemplateType\Covariant.',
				74,
			],
			[
				'PHPDoc tag @template T for interface InterfaceTemplateType\InvalidDefault has invalid default type InterfaceTemplateType\Zazzzu.',
				82,
			],
			[
				'Default type bool in PHPDoc tag @template T for interface InterfaceTemplateType\OutOfBoundsDefault is not subtype of bound type object.',
				90,
			],
			[
				'PHPDoc tag @template V for interface InterfaceTemplateType\RequiredAfterOptional does not have a default type but follows an optional @template U.',
				100,
			],
		]);
	}

	public function testInClass(): void
	{
		$this->analyse([__DIR__ . '/data/class-template.php'], []);
	}

}
