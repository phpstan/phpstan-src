<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FinalPrivateMethodRule> */
class FinalPrivateMethodRuleComposerPhpVersionRangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-7-only'];
	}

	protected function getRule(): Rule
	{
		return new FinalPrivateMethodRule();
	}

	public function testRuleWhenComposerRequiresPhp7Only(): void
	{
		$this->analyse([__DIR__ . '/data/final-private-method.php'], []);
	}

}
