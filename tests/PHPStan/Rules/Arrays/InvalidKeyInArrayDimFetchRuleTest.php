<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleTest extends RuleTestCase
{

	private bool $reportNonIntStringArrayKey = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(
			self::createReflectionProvider(),
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: true,
			checkImplicitMixed: true,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);
		return new InvalidKeyInArrayDimFetchRule(
			$ruleLevelHelper,
			self::getContainer()->getByType(PhpVersion::class),
			true,
			$this->reportNonIntStringArrayKey,
		);
	}

	public function testInvalidKey(): void
	{
		$errors = [
			[
				'Invalid array key type DateTimeImmutable.',
				7,
			],
			[
				'Invalid array key type array.',
				8,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				24,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				31,
			],
			[
				'Possibly invalid array key type mixed.',
				41,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				45,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				46,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				47,
			],
			[
				'Invalid array key type stdClass.',
				47,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				48,
			],
		];

		if (PHP_VERSION_ID >= 80100) {
			$errors[] = [
				'Invalid array key type float.',
				51,
			];
		}
		if (PHP_VERSION_ID >= 80500) {
			$errors[] = [
				'Invalid array key type null.',
				52,
			];
			$errors[] = [
				'Possibly invalid array key type string|null.',
				56,
			];
		}

		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch.php'], $errors);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6315(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6315.php'], [
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				18,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				19,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				20,
			],
			[
				'Invalid array key type Bug6315\FooEnum::B.',
				21,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				21,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				22,
			],
		]);
	}

	public function testBug13135(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13135.php'], [
			[
				'Possibly invalid array key type Tk of mixed.',
				15,
			],
		]);
	}

	public function testBug12273(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12273.php'], [
			[
				'Possibly invalid array key type mixed.',
				16,
			],
		]);
	}

	public function testBug12981(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12981.php'], [
			[
				'Invalid array key type array<int, int|string>.',
				31,
			],
			[
				'Invalid array key type array<int, int|string>.',
				33,
			],
			[
				'Possibly invalid array key type array<int, int|string>|int|string.',
				39,
			],
			[
				'Possibly invalid array key type array<int, int|string>|int|string.',
				41,
			],
		]);
	}

	public function testUnsetFalseKey(): void
	{
		$this->reportNonIntStringArrayKey = true;

		$this->analyse([__DIR__ . '/data/unset-false-key.php'], [
			[
				'Invalid array key type false.',
				6,
			],
			[
				'Invalid array key type false.',
				13,
			],
		]);
	}

}
