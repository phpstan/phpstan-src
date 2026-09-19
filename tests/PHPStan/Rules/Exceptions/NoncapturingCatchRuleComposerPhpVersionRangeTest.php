<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NoncapturingCatchRule>
 */
class NoncapturingCatchRuleComposerPhpVersionRangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-7-and-8'];
	}

	protected function getRule(): Rule
	{
		return new NoncapturingCatchRule();
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testRuleWhenComposerAllowsPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/noncapturing-catch.php'], [
			[
				'Non-capturing catch is supported only on PHP 8.0 and later.',
				12,
			],
		]);
	}

}
