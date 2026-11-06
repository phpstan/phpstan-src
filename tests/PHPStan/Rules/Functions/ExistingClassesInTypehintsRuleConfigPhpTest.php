<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExistingClassesInTypehintsRule>
 */
class ExistingClassesInTypehintsRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(ExistingClassesInTypehintsRule::class);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/function-definition-php-version-range.php'], [
			[
				'Deprecated in PHP 8.0: Required parameter $b follows optional parameter $a.',
				5,
			],
			[
				'Deprecated in PHP 8.1: Required parameter $b follows optional parameter $a.',
				9,
			],
			[
				'Deprecated in PHP 8.4: Parameter #1 $a (int) is implicitly nullable via default value null.',
				13,
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
