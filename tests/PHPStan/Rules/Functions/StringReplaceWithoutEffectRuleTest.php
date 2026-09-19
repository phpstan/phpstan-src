<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<StringReplaceWithoutEffectRule>
 */
class StringReplaceWithoutEffectRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new StringReplaceWithoutEffectRule(
			self::createReflectionProvider(),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';

		$this->analyse([__DIR__ . '/data/string-replace-without-effect.php'], [
			[
				'Parameter #1 $str (\'\\\\\') of function strtr does not contain any character from parameter #2 $from (\'/\'), call has no effect.',
				11,
			],
			[
				'Parameter #1 $str (\'\\\\\') of function strtr does not contain any character from parameter #2 $from (\'/\'), call has no effect.',
				12,
			],
			[
				'Parameter #2 $from (\'\') of function strtr is an empty string, call has no effect.',
				23,
			],
			[
				'Parameter #3 $to (\'\') of function strtr is an empty string, call has no effect.',
				24,
			],
			[
				'Parameter #2 $replace_pairs (array{}) of function strtr is empty, call has no effect.',
				43,
			],
			[
				'Parameter #1 $str (\'abc\') of function strtr does not contain any of the replaced strings from parameter #2 $replace_pairs (array{x: \'y\'}), call has no effect.',
				44,
			],
			[
				'Parameter #1 $str (\'abc\') of function strtr does not contain any of the replaced strings from parameter #2 $replace_pairs (array{xy: \'z\', qq: \'w\'}), call has no effect.',
				47,
			],
			[
				'Parameter #1 $str (\'abc\'|\'def\') of function strtr does not contain any character from parameter #2 $from (\'xy\'), call has no effect.',
				53,
			],
			[
				'Parameter #3 $subject (\'a/b\') of function str_replace does not contain parameter #1 $search (\'\\\\\'), call has no effect.',
				64,
			],
			[
				'Parameter #3 $subject (\'abc\') of function str_replace does not contain any of the strings from parameter #1 $search (array{\'x\', \'y\'}), call has no effect.',
				67,
			],
			[
				'Parameter #1 $search (array{}) of function str_replace is empty, call has no effect.',
				69,
			],
			[
				'Parameter #1 $search (\'\') of function str_replace is an empty string, call has no effect.',
				70,
			],
			[
				'Parameter #1 $search (\'\') of function str_replace is an empty string, call has no effect.',
				71,
			],
			[
				'Parameter #3 $subject (\'abc\') of function str_ireplace does not contain parameter #1 $search (\'X\'), call has no effect.',
				77,
			],
			[
				'Parameter #3 $subject (array{\'abc\', \'def\'}) of function str_replace does not contain parameter #1 $search (\'x\'), call has no effect.',
				90,
			],
			[
				'Parameter #3 $subject (\'abc\') of function str_replace does not contain parameter #1 $search (\'x\'), call has no effect.',
				100,
				$tipText,
			],
			[
				'Parameter #3 $subject (\'abc\') of function str_replace does not contain parameter #1 $search (\'x\'), call has no effect.',
				101,
				$tipText,
			],
			[
				'Parameter #3 $subject (\'abc\') of function str_replace does not contain parameter #1 $search (\'x\'), call has no effect.',
				106,
			],
			[
				'Parameter #1 $string (\'\\\\\') of function strtr does not contain any character from parameter #2 $from (\'/\'), call has no effect.',
				107,
			],
			[
				'Parameter #2 $from (\'ab\') and parameter #3 $to (\'ab\') of function strtr map every character to itself, call has no effect.',
				117,
			],
			[
				'Parameter #2 $from (\'abc\') and parameter #3 $to (\'ab\') of function strtr map every character to itself, call has no effect.',
				118,
			],
			[
				'Parameter #2 $replace_pairs (array{a: \'a\', bb: \'bb\'}) of function strtr maps every string to itself, call has no effect.',
				120,
			],
			[
				'Parameter #1 $search (\'/\') and parameter #2 $replace (\'/\') of function str_replace are the same, call has no effect.',
				126,
			],
			[
				'Parameter #2 $replace (\'\') of function substr_replace is an empty string and parameter #4 $length (0) is zero, call has no effect.',
				138,
			],
			[
				'Parameter #1 $pattern (array{}) of function preg_replace is empty, call has no effect.',
				149,
			],
			[
				'Parameter #1 $pattern (array{}) of function preg_replace_callback is empty, call has no effect.',
				151,
			],
			[
				'Parameter #1 $pattern (array{}) of function preg_replace_callback_array is empty, call has no effect.',
				152,
			],
		]);
	}

	public function testRuleWithoutTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;

		$this->analyse([__DIR__ . '/data/string-replace-without-effect-phpdoc-types.php'], []);
	}

}
