<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<ClassLevelUsedTraitsRule>
 */
class ClassLevelUsedTraitsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClassLevelUsedTraitsRule(
			self::getContainer()->getByType(PhpDocStringResolver::class),
			self::getContainer()->getByType(FileTypeMapper::class),
			new GenericAncestorsCheck(
				self::createReflectionProvider(),
				new GenericObjectTypeCheck(),
				new VarianceCheck(),
				new UnresolvableTypeHelper(),
				[],
				true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-level-used-traits.php'], [
			[
				'PHPDoc tag @use contains generic type ClassLevelUsedTraits\NongenericTrait<stdClass> but trait ClassLevelUsedTraits\NongenericTrait is not generic.',
				17,
			],
			[
				'Type int in generic type ClassLevelUsedTraits\GenericTrait<int> in PHPDoc tag @use is not subtype of template type T of object of trait ClassLevelUsedTraits\GenericTrait.',
				25,
			],
			[
				'Class ClassLevelUsedTraits\NoTraits has @use tag, but does not use any trait.',
				41,
			],
			[
				'The @use tag of class ClassLevelUsedTraits\WrongTrait describes ClassLevelUsedTraits\NongenericTrait but the class uses ClassLevelUsedTraits\GenericTrait.',
				47,
			],
			[
				'Generic type ClassLevelUsedTraits\GenericTrait<stdClass, Exception> in PHPDoc tag @use specifies 2 template types, but trait ClassLevelUsedTraits\GenericTrait supports only 1: T',
				55,
			],
			[
				'Call-site variance annotation of covariant Throwable in generic type ClassLevelUsedTraits\GenericTrait<covariant Throwable> in PHPDoc tag @use is not allowed.',
				63,
			],
			[
				'Type mixed in generic type ClassLevelUsedTraits\GenericTrait<mixed> in PHPDoc tag @use is not subtype of template type T of object of trait ClassLevelUsedTraits\GenericTrait.',
				74,
			],
			[
				'Interface ClassLevelUsedTraits\SomeInterface has @use tag, but does not use any trait.',
				90,
			],
		]);
	}

}
