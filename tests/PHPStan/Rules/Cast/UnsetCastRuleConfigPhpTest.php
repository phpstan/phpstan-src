<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnsetCastRule>
 */
class UnsetCastRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnsetCastRule();
	}

	public function testRulePhpVersionRangeSpanning80(): void
	{
		$this->analyse([__DIR__ . '/data/unset-cast-php-versions.php'], [
			[
				'The (unset) cast is no longer supported in PHP 8.0 and later.',
				11,
			],
			[
				'The (unset) cast is no longer supported in PHP 8.0 and later.',
				14,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/unset-cast-php-version.neon',
		];
	}

}
