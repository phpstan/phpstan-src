<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<UsedTraitsRule>
 */
class UsedTraitsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UsedTraitsRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new GenericAncestorsCheck(
				$this->createReflectionProvider(),
				new GenericObjectTypeCheck(),
				new VarianceCheck(true, true),
				true,
				[],
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/used-traits.php'], [
			[
				'PHPDoc tag @use contains generic type UsedTraits\NongenericTrait<stdClass> but trait UsedTraits\NongenericTrait is not generic.',
				20,
			],
			[
				'Type int in generic type UsedTraits\GenericTrait<int> in PHPDoc tag @use is not subtype of template type T of object of trait UsedTraits\GenericTrait.',
				31,
			],
			[
				'Class UsedTraits\Baz uses generic trait UsedTraits\GenericTrait but does not specify its types: T',
				38,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Generic type UsedTraits\GenericTrait<stdClass, Exception> in PHPDoc tag @use specifies 2 template types, but trait UsedTraits\GenericTrait supports only 1: T',
				46,
			],
			[
				'The @use tag of trait UsedTraits\NestedTrait describes UsedTraits\NongenericTrait but the trait uses UsedTraits\GenericTrait.',
				54,
			],
			[
				'Trait UsedTraits\NestedTrait uses generic trait UsedTraits\GenericTrait but does not specify its types: T',
				54,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Type projection covariant Throwable in generic type UsedTraits\GenericTrait<covariant Throwable> in PHPDoc tag @use is not allowed.',
				69,
			],
		]);
	}

}
