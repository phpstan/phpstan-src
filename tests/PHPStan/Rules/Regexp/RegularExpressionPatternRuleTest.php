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
					8,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					9,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					13,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					14,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					18,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					19,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					23,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					24,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					28,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					29,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					31,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					31,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					34,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					35,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					37,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					37,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					40,
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
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					45,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 1 in pattern: ~(~',
					45,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)', $messagePart),
					59,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)', $messagePart),
					60,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 7 in pattern: ~((?:.*)~',
					61,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)nono', $messagePart),
					63,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok(?:.*)nope', $messagePart),
					64,
				],
				[
					'Regex pattern is invalid: Compilation failed: missing closing parenthesis at offset 7 in pattern: ~((?:.*)~',
					65,
				],
				[
					sprintf('Regex pattern is invalid: Delimiter must not be %s in pattern: nok', $messagePart),
					97,
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
