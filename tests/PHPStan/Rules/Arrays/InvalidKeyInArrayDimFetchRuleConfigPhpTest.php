<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(InvalidKeyInArrayDimFetchRule::class);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-php-version-range.php'], [
			[
				'Invalid array key type float.',
				11,
			],
			[
				'Invalid array key type null.',
				12,
			],
		]);
	}

	public function testBug15729(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15729.php'], [
			[
				'Possibly invalid array key type string|null.',
				14,
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
