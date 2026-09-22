<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\PrintfFormatParser;

/**
 * @extends RuleTestCase<PrintfParametersRule>
 */
class PrintfParametersRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PrintfParametersRule(
			new PrintfFormatParser(),
			self::createReflectionProvider(),
		);
	}

	public function testHhSpecifiersInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/printf-format-parser-php-versions.php'], [
			[
				'Call to sprintf contains an invalid placeholder.',
				12,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/printf-format-parser-php-version.neon',
		];
	}

}
