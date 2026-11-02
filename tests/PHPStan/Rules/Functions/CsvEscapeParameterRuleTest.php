<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CsvEscapeParameterRule>
 */
#[RequiresPhp('>= 8.0.0')]
class CsvEscapeParameterRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80400;

	protected function getRule(): Rule
	{
		return new CsvEscapeParameterRule(self::createReflectionProvider(), new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp84(): void
	{
		$this->phpVersionId = 80300;
		$this->analyse([__DIR__ . '/data/csv-escape-parameter.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/csv-escape-parameter.php', __DIR__ . '/data/csv-escape-parameter.php.fixed');
	}

}
