<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false);
		return new InvalidKeyInArrayDimFetchRule($ruleLevelHelper, true);
	}

	public function testInvalidKey(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch.php'], [
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
		]);
	}

	public function testInvalidKeyEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch-enum.php'], [
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::A.',
				18,
			],
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::A.',
				19,
			],
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::A.',
				20,
			],
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::B.',
				21,
			],
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::A.',
				21,
			],
			[
				'Invalid array key type InvalidKeyArrayDimFetchEnum\FooEnum::A.',
				22,
			],
		]);
	}

}
