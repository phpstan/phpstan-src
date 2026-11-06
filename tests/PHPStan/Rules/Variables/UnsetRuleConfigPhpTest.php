<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnsetRule>
 */
class UnsetRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(UnsetRule::class);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/unset-php-version-range.php'], [
			[
				'Cannot unset property UnsetPhpVersionRange\\Foo::$p because it might have hooks in a subclass.',
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
