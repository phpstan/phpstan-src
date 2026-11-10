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
				'Call to str_replace is using fewer replacements than search values.',
				12,
			],
			[
				'Call to str_ireplace is using fewer replacements than search values.',
				37,
			],
			[
				'Call to str_replace is using fewer replacements than search values.',
				41,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to preg_replace is using fewer replacements than search values.',
				47,
			],
			[
				'Call to preg_filter is using fewer replacements than search values.',
				50,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testNamedArgs(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3889-named-args.php'], [
			[
				'Call to str_replace is using fewer replacements than search values.',
				9,
			],
		]);
	}

}
