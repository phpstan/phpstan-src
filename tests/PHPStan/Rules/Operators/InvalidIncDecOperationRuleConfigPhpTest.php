<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidIncDecOperationRule>
 */
class InvalidIncDecOperationRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(InvalidIncDecOperationRule::class);
	}

	public function testDecNonNumericStringInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/dec-non-numeric-string.php'], [
			[
				'Cannot use -- on \'a\'.',
				21,
				'Operator -- is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_decrement().',
			],
			[
				'Cannot use -- on string.',
				23,
				'Operator -- is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_decrement().',
			],
			[
				'Cannot use -- on null.',
				30,
				'Operator -- is deprecated for null.',
			],
			[
				'Cannot use ++ on bool.',
				32,
				'Operator ++ is deprecated for bool.',
			],
			[
				'Cannot use -- on bool.',
				40,
				'Operator -- is deprecated for bool.',
			],
		]);
	}

	public function testIncNonNumericStringInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/inc-non-numeric-string.php'], [
			[
				'Cannot use ++ on \'a\'.',
				21,
				'Operator ++ is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_increment().',
			],
			[
				'Cannot use ++ on string.',
				23,
				'Operator ++ is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_increment().',
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range.neon',
		];
	}

}
