<?php declare(strict_types = 1);

namespace PHPStan\Rules\String;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InterpolatedStringRule>
 */
class InterpolatedStringRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new InterpolatedStringRule(new PhpVersion(PHP_VERSION_ID));
	}

	#[RequiresPhp('>= 8.2')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/interpolated-string.php'], [
			[
				'Using ${var} in strings is deprecated in PHP 8.2. Use {$var} instead.',
				17,
			],
			[
				'Using ${expr} (variable variables) in strings is deprecated in PHP 8.2. Use {${expr}} instead.',
				18,
			],
			[
				'Using ${expr} (variable variables) in strings is deprecated in PHP 8.2. Use {${expr}} instead.',
				19,
			],
			[
				'Using ${var} in strings is deprecated in PHP 8.2. Use {$var} instead.',
				20,
			],
			[
				'Using ${expr} (variable variables) in strings is deprecated in PHP 8.2. Use {${expr}} instead.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/interpolated-string.php', __DIR__ . '/data/interpolated-string.php.fixed');
	}

}
