<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($broker);

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
		]);
	}

	public function testInvalidKeyMixed(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch-mixed.php'], [
			[
				'Possibly invalid array key type mixed.',
				11,
			],
		]);
	}

}
