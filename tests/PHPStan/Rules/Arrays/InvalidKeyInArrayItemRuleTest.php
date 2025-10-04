<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidKeyInArrayItemRule>
 */
class InvalidKeyInArrayItemRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(self::createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, false, true);

		return new InvalidKeyInArrayItemRule(
			$ruleLevelHelper,
			self::getContainer()->getByType(PhpVersion::class),
		);
	}

	public function testInvalidKey(): void
	{
		$errors = [
			[
				'Invalid array key type DateTimeImmutable.',
				12,
			],
			[
				'Invalid array key type array.',
				13,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				14,
			],
		];

		if (PHP_VERSION_ID >= 80100) {
			$errors[] = [
				'Invalid array key type float.',
				26,
			];
		}
		if (PHP_VERSION_ID >= 80500) {
			$errors[] = [
				'Invalid array key type null.',
				27,
			];
		}

		$this->analyse([__DIR__ . '/data/invalid-key-array-item.php'], $errors);
	}

	public function testInvalidMixedKey(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$errors = [
			[
				'Invalid array key type DateTimeImmutable.',
				12,
			],
			[
				'Invalid array key type array.',
				13,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				14,
			],
			[
				'Possibly invalid array key type mixed.',
				21,
			],
		];

		if (PHP_VERSION_ID >= 80100) {
			$errors[] = [
				'Invalid array key type float.',
				26,
			];
		}
		if (PHP_VERSION_ID >= 80500) {
			$errors[] = [
				'Invalid array key type null.',
				27,
			];
		}

		$this->analyse([__DIR__ . '/data/invalid-key-array-item.php'], $errors);
	}

	public function testInvalidKeyInList(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-list.php'], [
			[
				'Invalid array key type DateTimeImmutable.',
				7,
			],
			[
				'Invalid array key type array.',
				8,
			],
		]);
	}

	public function testInvalidKeyShortArray(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-short-array.php'], [
			[
				'Invalid array key type DateTimeImmutable.',
				7,
			],
			[
				'Invalid array key type array.',
				8,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testInvalidKeyEnum(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-item-enum.php'], [
			[
				'Invalid array key type InvalidKeyArrayItemEnum\FooEnum::A.',
				14,
			],
		]);
	}

}
