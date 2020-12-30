<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<TraitTemplateTypeRule>
 */
class TraitTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new TraitTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker), new GenericObjectTypeCheck(), $typeAliasResolver, true)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/trait-template.php'], [
			[
				'PHPDoc tag @template for trait TraitTemplateType\Foo cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for trait TraitTemplateType\Bar has invalid bound type TraitTemplateType\Zazzzu.',
				16,
			],
			[
				'PHPDoc tag @template T for trait TraitTemplateType\Baz with bound type float is not supported.',
				24,
			],
			[
				'PHPDoc tag @template for trait TraitTemplateType\Lorem cannot have existing type alias TypeAlias as its name.',
				32,
			],
		]);
	}

}
