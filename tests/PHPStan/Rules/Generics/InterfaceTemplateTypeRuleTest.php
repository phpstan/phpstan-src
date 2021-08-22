<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InterfaceTemplateTypeRule>
 */
class InterfaceTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new InterfaceTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), new GenericObjectTypeCheck(), $typeAliasResolver, true)
		);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/interface-template.php';

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
		]);
	}

}
