<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

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

		return new InvalidKeyInArrayItemRule($ruleLevelHelper);
	}

	public function testInvalidKey(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-item.php'], [
			[
				'Invalid array key type DateTimeImmutable.',
				13,
			],
			[
				'Invalid array key type array.',
				14,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				15,
			],
		]);
	}

	public function testInvalidMixedKey(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/invalid-key-array-item.php'], [
			[
				'Invalid array key type DateTimeImmutable.',
				13,
			],
			[
				'Invalid array key type array.',
				14,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				15,
			],
			[
				'Possibly invalid array key type mixed.',
				22,
			],
		]);
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
