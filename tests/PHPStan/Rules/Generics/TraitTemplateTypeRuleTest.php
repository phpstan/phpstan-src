<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<TraitTemplateTypeRule>
 */
class TraitTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new TraitTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker, true), new GenericObjectTypeCheck(), $typeAliasResolver, true),
		);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/trait-template.php';

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
				'PHPDoc tag @template for trait TraitTemplateType\Lorem cannot have existing type alias TypeAlias as its name.',
				33,
			],
			[
				'PHPDoc tag @template for trait TraitTemplateType\Ipsum cannot have existing type alias LocalAlias as its name.',
				45,
			],
			[
				'PHPDoc tag @template for trait TraitTemplateType\Ipsum cannot have existing type alias ImportedAlias as its name.',
				45,
			],
			[
				'Type projection covariant int in generic type TraitTemplateType\Dolor<covariant int> in PHPDoc tag @template U is redundant, template type T of class TraitTemplateType\Dolor has the same variance.',
				64,
				'You can safely remove the type projection.',
			],
			[
				'Type projection contravariant int in generic type TraitTemplateType\Dolor<contravariant int> in PHPDoc tag @template W is conflicting with variance of template type T of class TraitTemplateType\Dolor.',
				64,
			],
		]);
	}

}
