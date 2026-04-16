<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidIncDecOperationRule>
 */
class InvalidIncDecOperationRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		return new InvalidIncDecOperationRule(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: $this->checkExplicitMixed,
				checkImplicitMixed: $this->checkImplicitMixed,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	public function testRule(): void
	{
		$errors = [
			[
				'Cannot use ++ on a non-variable.',
				11,
			],
			[
				'Cannot use -- on a non-variable.',
				12,
			],
			[
				'Cannot use ++ on stdClass.',
				17,
			],
			[
				'Cannot use ++ on InvalidIncDec\\ClassWithToString.',
				19,
			],
			[
				'Cannot use -- on InvalidIncDec\\ClassWithToString.',
				21,
			],
			[
				'Cannot use ++ on array{}.',
				23,
			],
			[
				'Cannot use -- on array{}.',
				25,
			],
			[
				'Cannot use ++ on resource.',
				28,
			],
			[
				'Cannot use -- on resource.',
				32,
			],
		];

		if (PHP_VERSION_ID >= 80300) {
			$errors[] = [
				'Cannot use ++ on true.',
				36,
				'Operator ++ is deprecated for true.',
			];
			$errors[] = [
				'Cannot use -- on false.',
				38,
				'Operator -- is deprecated for false.',
			];
			$errors[] = [
				'Cannot use -- on null.',
				42,
				'Operator -- is deprecated for null.',
			];
		}

		$this->analyse([__DIR__ . '/data/invalid-inc-dec.php'], $errors);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testMixed(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/invalid-inc-dec-mixed.php'], [
			[
				'Cannot use ++ on T of mixed.',
				12,
			],
			[
				'Cannot use ++ on T of mixed.',
				14,
			],
			[
				'Cannot use -- on T of mixed.',
				16,
			],
			[
				'Cannot use -- on T of mixed.',
				18,
			],
			[
				'Cannot use ++ on mixed.',
				24,
			],
			[
				'Cannot use ++ on mixed.',
				26,
			],
			[
				'Cannot use -- on mixed.',
				28,
			],
			[
				'Cannot use -- on mixed.',
				30,
			],
			[
				'Cannot use ++ on mixed.',
				36,
			],
			[
				'Cannot use ++ on mixed.',
				38,
			],
			[
				'Cannot use -- on mixed.',
				40,
			],
			[
				'Cannot use -- on mixed.',
				42,
			],
		]);
	}

	public function testUnion(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-inc-dec-union.php'], [
			[
				'Cannot use ++ on array|bool|float|int|object|string|null.',
				24,
				PHP_VERSION_ID >= 80500 ?
					'• Operator ++ is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_increment().' . "\n" .
					'• Operator ++ is deprecated for bool.'
					: (PHP_VERSION_ID >= 80300 ? 'Operator ++ is deprecated for bool.' : null),
			],
			[
				'Cannot use -- on array|bool|float|int|object|string|null.',
				26,
				PHP_VERSION_ID >= 80300 ?
					'• Operator -- is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use str_decrement().' . "\n" .
					'• Operator -- is deprecated for null.' . "\n" .
					'• Operator -- is deprecated for bool.'
					: null,
			],
			[
				'Cannot use ++ on (array|object).',
				29,
			],
			[
				'Cannot use -- on (array|object).',
				31,
			],
		]);
	}

	public function testDecNonNumericString(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80300) {
			$errors = [
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
			];
		}

		$this->analyse([__DIR__ . '/data/dec-non-numeric-string.php'], $errors);
	}

	public function testIncNonNumericString(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80500) {
			$errors = [
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
			];
		}

		$this->analyse([__DIR__ . '/data/inc-non-numeric-string.php'], $errors);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBcMathNumber(): void
	{
		$this->analyse([__DIR__ . '/data/inc-dec-bcmath-number.php'], []);
	}

}
