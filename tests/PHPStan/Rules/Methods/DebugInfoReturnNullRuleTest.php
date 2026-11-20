<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<DebugInfoReturnNullRule>
 */
#[RequiresPhp('>= 8.2.0')]
class DebugInfoReturnNullRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DebugInfoReturnNullRule();
	}

	public function testRule(): void
	{
		$tip = 'Return an empty array instead.';
		$this->analyse([__DIR__ . '/data/debug-info-return-null.php'], [
			['Returning null from DebugInfoReturnNull\ReturnsNull::__debugInfo() is deprecated in PHP 8.5.', 10, $tip],
			['Returning null from DebugInfoReturnNull\ReturnsNullConditionally::__DebugInfo() is deprecated in PHP 8.5.', 26, $tip],
			['Returning null from DebugInfoReturnNull\ReturnsNullConditionally::__DebugInfo() is deprecated in PHP 8.5.', 31, $tip],
			['Returning null from DebugInfoReturnNull\ReturnsNullFromCall::__debugInfo() is deprecated in PHP 8.5.', 44, $tip],
			['Returning null from DebugInfoReturnNull\EmptyReturn::__debugInfo() is deprecated in PHP 8.5.', 62, $tip],
			['Returning null from DebugInfoReturnNull\ImplicitReturn::__debugInfo() is deprecated in PHP 8.5.', 73, 'The method returns null implicitly. Return an empty array instead.'],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/debug-info-return-null-php-version.php'], [
			['Returning null from DebugInfoReturnNullPhpVersion\After::__debugInfo() is deprecated in PHP 8.5.', 23, 'Return an empty array instead.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/debug-info-return-null.php', __DIR__ . '/data/debug-info-return-null.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80400-80500.neon',
		];
	}

}
