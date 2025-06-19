<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Regex\RegexExpressionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RegularExpressionPatternRule>
 */
class RegularExpressionPatternRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RegularExpressionPatternRule(
			self::getContainer()->getByType(RegexExpressionHelper::class),
		);
	}

	public function testValidRegexPattern(): void
	{
		$messagePart = 'alphanumeric or backslash';
		if (PHP_VERSION_ID >= 80200) {
			$messagePart = 'alphanumeric, backslash, or NUL';
		}
		if (PHP_VERSION_ID >= 80400) {
			$messagePart = 'alphanumeric, backslash, or NUL byte';
		}

		$this->analyse(
			[__DIR__ . '/data/valid-regex-pattern.php'],
			[
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					6,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					7,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					11,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					12,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					16,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					17,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					21,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					22,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					26,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					27,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					29,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					29,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					32,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					33,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					35,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					35,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					38,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					39,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					41,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					41,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					43,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					43,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)', $messagePart),
					57,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)', $messagePart),
					58,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 7 in pattern: ~((?:.*)~',
					59,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)nono', $messagePart),
					61,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)nope', $messagePart),
					62,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 7 in pattern: ~((?:.*)~',
					63,
				],
			],
		);
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $errors
	 */
	#[DataProvider('dataArrayShapePatterns')]
	public function testArrayShapePatterns(string $file, array $errors): void
	{
		$this->analyse(
			[$file],
			$errors,
		);
	}

	public static function dataArrayShapePatterns(): iterable
	{
		yield [
			__DIR__ . '/../../Analyser/nsrt/preg_match_all_shapes.php',
			[],
		];

		yield [
			__DIR__ . '/../../Analyser/nsrt/preg_match_shapes.php',
			[
				[
					"Regex pattern is invalid: Unknown modifier 'y' in pattern: /(foo)(bar)(baz)/xyz",
					124,
				],
			],
		];

		if (PHP_VERSION_ID >= 80000) {
			yield [
				__DIR__ . '/../../Analyser/nsrt/preg_match_shapes_php80.php',
				[],
			];
		}

		if (PHP_VERSION_ID >= 80200) {
			yield [
				__DIR__ . '/../../Analyser/nsrt/preg_match_shapes_php82.php',
				[],
			];
		}

		yield [
			__DIR__ . '/../../Analyser/nsrt/preg_replace_callback_shapes.php',
			[],
		];
		yield [
			__DIR__ . '/../../Analyser/nsrt/preg_replace_callback_shapes-php72.php',
			[],
		];
	}

}
