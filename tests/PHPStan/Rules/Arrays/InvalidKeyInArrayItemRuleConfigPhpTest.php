<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidKeyInArrayItemRule>
 */
class InvalidKeyInArrayItemRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(InvalidKeyInArrayItemRule::class);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-php-version-range.php'], [
			[
				'Invalid array key type float.',
				7,
			],
			[
				'Invalid array key type null.',
				8,
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
