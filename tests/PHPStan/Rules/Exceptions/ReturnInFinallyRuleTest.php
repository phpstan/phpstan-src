<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReturnInFinallyRule>
 */
class ReturnInFinallyRuleTest extends RuleTestCase
{

	private bool $checkWholePhpVersionRange;

	protected function getRule(): Rule
	{
		return new ReturnInFinallyRule($this->checkWholePhpVersionRange);
	}

	public function testRuleWithWholePhpVersionRange(): void
	{
		$this->checkWholePhpVersionRange = true;
		$this->analyse([__DIR__ . '/data/return-in-finally.php'], [
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				9,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				17,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				53,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				55,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				57,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				70,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				82,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				86,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				90,
			],
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				102,
			],
		]);
	}

	public function testRule(): void
	{
		$this->checkWholePhpVersionRange = false;
		$this->analyse([__DIR__ . '/data/return-in-finally.php'], [
			[
				'Returning from a finally block is deprecated in PHP 8.6.',
				102,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/return-in-finally-php-version.neon',
		];
	}

}
