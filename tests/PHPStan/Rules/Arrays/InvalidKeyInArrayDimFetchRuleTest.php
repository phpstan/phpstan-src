<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_filter;
use function array_values;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false);
		return new InvalidKeyInArrayDimFetchRule($ruleLevelHelper, true, new PhpVersion(PHP_VERSION_ID));
	}

	public function testInvalidKey(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch.php'], array_values(array_filter([
			[
				'Invalid array key type DateTimeImmutable.',
				7,
			],
			[
				'Invalid array key type array.',
				8,
			],
			PHP_VERSION_ID >= 80_100
				? [
					'Using float as array key emits deprecation notice.',
					10,
				]
				: null,
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
		])));
	}

	public function testBug6315(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

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

}
