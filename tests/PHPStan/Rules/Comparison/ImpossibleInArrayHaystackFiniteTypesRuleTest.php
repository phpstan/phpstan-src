<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ImpossibleInArrayHaystackFiniteTypesRule>
 */
class ImpossibleInArrayHaystackFiniteTypesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ImpossibleInArrayHaystackFiniteTypesRule(
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			true,
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/impossible-in-array-finite-types.php'], [
			[
				'Value ImpossibleInArrayFiniteTypes\Foo::ONE in the haystack passed to in_array() can never be identical to the needle type int.',
				19,
			],
			[
				'Value ImpossibleInArrayFiniteTypes\Foo::ONE in the haystack passed to in_array() can never be equal to the needle type int.',
				26,
			],
			[
				'Value ImpossibleInArrayFiniteTypes\Foo::ONE in the haystack passed to array_search() can never be identical to the needle type int.',
				33,
			],
			[
				'Value ImpossibleInArrayFiniteTypes\Foo::ONE in the haystack passed to array_keys() can never be identical to the needle type int.',
				38,
			],
			[
				'Value ImpossibleInArrayFiniteTypes\Foo::TWO in the haystack passed to in_array() can never be identical to the needle type ImpossibleInArrayFiniteTypes\Foo::ONE.',
				48,
			],
			[
				'Value \'installed\' in the haystack passed to in_array() can never be identical to the needle type mixed.',
				98,
				'Type \'installed\' has already been eliminated from mixed.',
			],
		]);
	}

}
