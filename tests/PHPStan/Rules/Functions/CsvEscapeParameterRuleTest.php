<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CsvEscapeParameterRule>
 */
#[RequiresPhp('>= 8.0.0')]
class CsvEscapeParameterRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CsvEscapeParameterRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$tip = 'Pass \'\\\\\' to keep the current behaviour, or an empty string to disable the proprietary escape mechanism.';
		$this->analyse([__DIR__ . '/data/csv-escape-parameter.php'], [
			['Calling fgetcsv() without the $escape parameter is deprecated in PHP 8.4.', 15, $tip],
			['Calling fgetcsv() without the $escape parameter is deprecated in PHP 8.4.', 16, $tip],
			['Calling fputcsv() without the $escape parameter is deprecated in PHP 8.4.', 17, $tip],
			['Calling str_getcsv() without the $escape parameter is deprecated in PHP 8.4.', 18, $tip],
			['Calling str_getcsv() without the $escape parameter is deprecated in PHP 8.4.', 19, $tip],
			['Calling SplFileObject::setCsvControl() without the $escape parameter is deprecated in PHP 8.4.', 20, $tip],
			['Calling SplFileObject::setCsvControl() without the $escape parameter is deprecated in PHP 8.4.', 21, $tip],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/csv-escape-parameter-php-version.php'], [
			['Calling str_getcsv() without the $escape parameter is deprecated in PHP 8.4.', 12, 'Pass \'\\\\\' to keep the current behaviour, or an empty string to disable the proprietary escape mechanism.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/csv-escape-parameter.php', __DIR__ . '/data/csv-escape-parameter.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80300-80400.neon',
		];
	}

}
