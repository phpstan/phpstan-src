<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReplaceCountMismatchRule>
 */
class ReplaceCountMismatchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReplaceCountMismatchRule(
			self::createReflectionProvider(),
			new PhpVersion(PHP_VERSION_ID),
			true,
			true,
		);
	}

	public function testBug3889(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3889.php'], [
			[
				'Parameter #2 $replace of function str_replace has fewer elements (2) than parameter #1 $search (3), the remaining values will be replaced with an empty string.',
				12,
			],
			[
				'Parameter #2 $replace of function str_ireplace has fewer elements (1) than parameter #1 $search (3), the remaining values will be replaced with an empty string.',
				36,
			],
			[
				'Parameter #2 $replace of function str_replace has fewer elements (1) than parameter #1 $search (2), the remaining values will be replaced with an empty string.',
				40,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Parameter #2 $replacement of function preg_replace has fewer elements (1) than parameter #1 $pattern (2), the remaining values will be replaced with an empty string.',
				46,
			],
			[
				'Parameter #2 $replacement of function preg_filter has fewer elements (1) than parameter #1 $pattern (2), the remaining values will be replaced with an empty string.',
				49,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNamedArgs(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3889-named-args.php'], [
			[
				'Parameter #2 $replace of function str_replace has fewer elements (1) than parameter #1 $search (2), the remaining values will be replaced with an empty string.',
				9,
			],
		]);
	}

}
