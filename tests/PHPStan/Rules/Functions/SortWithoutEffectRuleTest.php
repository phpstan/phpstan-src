<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<SortWithoutEffectRule>
 */
class SortWithoutEffectRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SortWithoutEffectRule(
			self::createReflectionProvider(),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
	}

	public function testRule(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';

		$this->analyse([__DIR__ . '/data/sort-without-effect.php'], [
			[
				'Parameter #1 $array (list<string>) of function ksort is a list, call has no effect.',
				8,
				$tipText,
			],
			[
				'Parameter #1 $array (list<string>) of function ksort is a list, call has no effect.',
				14,
				$tipText,
			],
			[
				'Parameter #1 $array (list<string>) of function ksort is a list, call has no effect.',
				20,
				$tipText,
			],
			[
				'Parameter #1 $array (array{\'a\', \'b\'}) of function ksort is a list, call has no effect.',
				70,
			],
			[
				'Parameter #1 $array (array{}) of function ksort is empty, call has no effect.',
				88,
			],
			[
				'Parameter #1 $array (array{}) of function krsort is empty, call has no effect.',
				94,
			],
			[
				'Parameter #1 $array (array{}) of function asort is empty, call has no effect.',
				100,
			],
			[
				'Parameter #1 $array (array{}) of function arsort is empty, call has no effect.',
				106,
			],
			[
				'Parameter #1 $array (array{}) of function sort is empty, call has no effect.',
				112,
			],
			[
				'Parameter #1 $array (array{}) of function rsort is empty, call has no effect.',
				118,
			],
			[
				'Parameter #1 $array (array{}) of function usort is empty, call has no effect.',
				124,
			],
			[
				'Parameter #1 $array (array{}) of function uasort is empty, call has no effect.',
				130,
			],
			[
				'Parameter #1 $array (array{}) of function uksort is empty, call has no effect.',
				136,
			],
			[
				'Parameter #1 $array (array{}) of function shuffle is empty, call has no effect.',
				142,
			],
			[
				'Parameter #1 $array (array{}) of function natsort is empty, call has no effect.',
				148,
			],
			[
				'Parameter #1 $array (array{}) of function natcasesort is empty, call has no effect.',
				154,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function ksort has at most 1 element, call has no effect.',
				160,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function krsort has at most 1 element, call has no effect.',
				166,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function asort has at most 1 element, call has no effect.',
				172,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function arsort has at most 1 element, call has no effect.',
				178,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function uasort has at most 1 element, call has no effect.',
				184,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function uksort has at most 1 element, call has no effect.',
				190,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function natsort has at most 1 element, call has no effect.',
				196,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo: int}) of function natcasesort has at most 1 element, call has no effect.',
				202,
				$tipText,
			],
			[
				'Parameter #1 $array (array{int}) of function sort has at most 1 element, call has no effect.',
				232,
				$tipText,
			],
			[
				'Parameter #1 $array (array{int}) of function rsort has at most 1 element, call has no effect.',
				238,
				$tipText,
			],
			[
				'Parameter #1 $array (array{int}) of function usort has at most 1 element, call has no effect.',
				244,
				$tipText,
			],
			[
				'Parameter #1 $array (array{int}) of function shuffle has at most 1 element, call has no effect.',
				250,
				$tipText,
			],
			[
				'Parameter #1 $array (array{bar: int}|array{foo: int}) of function ksort has at most 1 element, call has no effect.',
				262,
				$tipText,
			],
			[
				'Parameter #1 $array (array{foo?: int}) of function ksort has at most 1 element, call has no effect.',
				273,
				$tipText,
			],
			[
				'Parameter #1 $array (array{0?: int}) of function sort has at most 1 element, call has no effect.',
				279,
				$tipText,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testNamedArguments(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';

		$this->analyse([__DIR__ . '/data/sort-without-effect-named-args.php'], [
			[
				'Parameter #1 $array (list<string>) of function ksort is a list, call has no effect.',
				10,
				$tipText,
			],
		]);
	}

}
