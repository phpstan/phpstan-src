<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_filter;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidKeyInArrayItemRule>
 */
class InvalidKeyInArrayItemRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidKeyInArrayItemRule(true, new PhpVersion(PHP_VERSION_ID));
	}

	public function testInvalidKey(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-item.php'], array_filter([
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
			PHP_VERSION_ID >= 80_100
				? [
					'Using float as array key emits deprecation notice.',
					16,
				]
				: null,
		]));
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

	public function testInvalidKeyEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/invalid-key-array-item-enum.php'], [
			[
				'Invalid array key type InvalidKeyArrayItemEnum\FooEnum::A.',
				14,
			],
		]);
	}

}
