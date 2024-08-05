<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\RegexExpressionHelper;
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

	public function testValidRegexPatternBefore73(): void
	{
		if (PHP_VERSION_ID >= 70300) {
			$this->markTestSkipped('This test requires PHP < 7.3.0');
		}

		$this->analyse(
			[__DIR__ . '/data/valid-regex-pattern.php'],
			[
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					6,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					7,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					11,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					12,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					16,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					17,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					21,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					22,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					26,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					27,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					29,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					29,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					32,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					33,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					35,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					35,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					38,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					39,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					41,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					41,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok',
					43,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 1 in pattern: ~(~',
					43,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok(?:.*)',
					57,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok(?:.*)',
					58,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 7 in pattern: ~((?:.*)~',
					59,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok(?:.*)nono',
					61,
				],
				[
					'Regex pattern is invalid: Delimiter must not be alphanumeric or backslash in pattern: nok(?:.*)nope',
					62,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing ) at offset 7 in pattern: ~((?:.*)~',
					63,
				],
			],
		);
	}

	public function testValidRegexPatternAfter73(): void
	{
		if (PHP_VERSION_ID < 70300) {
			$this->markTestSkipped('This test requires PHP >= 7.3.0');
		}

		$messagePart = 'alphanumeric or backslash';
		if (PHP_VERSION_ID >= 80200) {
			$messagePart = 'alphanumeric, backslash, or NUL';
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
	 * @dataProvider dataArrayShapePatterns
	 */
	public function testArrayShapePatterns(string $file, array $errors): void
	{
		$this->analyse(
			[$file],
			$errors,
		);
	}

	public function dataArrayShapePatterns(): iterable
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

		yield [
			__DIR__ . '/../../Analyser/nsrt/preg_match_shapes_php80.php',
			[],
		];
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
