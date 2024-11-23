<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FinalPrivateMethodRule> */
class FinalPrivateMethodRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalPrivateMethodRule();
	}

	public function testRulePhpVersions(): void
	{
		$this->analyse([__DIR__ . '/data/final-private-method-config-phpversion.php'], [
			[
				'Private method FinalPrivateMethodConfigPhpVersions\PhpVersionViaNEONConfg::foo() cannot be final as it is never overridden by other classes.',
				8,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/final-private-php-version.neon',
		];
	}

}
