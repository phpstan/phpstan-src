<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InterfaceTemplateTypeRule>
 */
class InterfaceTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new InterfaceTemplateTypeRule(
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker, true), new GenericObjectTypeCheck(), $typeAliasResolver, true),
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
		]);
	}

	public function testInClass(): void
	{
		$this->analyse([__DIR__ . '/data/class-template.php'], []);
	}

}
