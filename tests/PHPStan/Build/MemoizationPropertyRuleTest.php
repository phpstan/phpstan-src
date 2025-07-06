<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MemoizationPropertyRule>
 */
final class MemoizationPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MemoizationPropertyRule(self::getContainer()->getByType(FileHelper::class), false);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/memoization-property.php'], [
			[
				'This initializing if statement can be replaced with null coalescing assignment operator (??=).',
				13,
			],
			[
				'This initializing if statement can be replaced with null coalescing assignment operator (??=).',
				22,
			],
			[
				'This initializing if statement can be replaced with null coalescing assignment operator (??=).',
				55,
			],
			[
				'This initializing if statement can be replaced with null coalescing assignment operator (??=).',
				85,
			],
			[
				'This initializing if statement can be replaced with null coalescing assignment operator (??=).',
				96,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/memoization-property.php', __DIR__ . '/data/memoization-property.php.fixed');
	}

}
