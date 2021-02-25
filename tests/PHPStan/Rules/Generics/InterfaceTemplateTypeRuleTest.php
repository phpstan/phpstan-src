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
		return new InterfaceTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), ['TypeAlias' => 'int'], true)
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
				'PHPDoc tag @template T for interface InterfaceTemplateType\Baz with bound type float is not supported.',
				24,
			],
			[
				'PHPDoc tag @template for interface InterfaceTemplateType\Lorem cannot have existing type alias TypeAlias as its name.',
				32,
			],
			[
				'PHPDoc tag @template T for interface InterfaceTemplateType\UnionBound with bound type InterfaceTemplateType\NormalT<stdClass> is not supported.',
				44,
			],
		]);
	}

}
