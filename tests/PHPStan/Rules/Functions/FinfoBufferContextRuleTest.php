<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<FinfoBufferContextRule>
 */
#[RequiresPhp('>= 8.0.0')]
class FinfoBufferContextRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinfoBufferContextRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$tip = 'The parameter has no effect, remove it.';
		$this->analyse([__DIR__ . '/data/finfo-buffer-context.php'], [
			['Passing the $context parameter to finfo_buffer() is deprecated in PHP 8.5.', 13, $tip],
			['Passing the $context parameter to finfo_buffer() is deprecated in PHP 8.5.', 14, $tip],
			['Passing the $context parameter to finfo_buffer() is deprecated in PHP 8.5.', 15, $tip],
			['Passing the $context parameter to finfo::buffer() is deprecated in PHP 8.5.', 16, $tip],
			['Passing the $context parameter to finfo::buffer() is deprecated in PHP 8.5.', 17, $tip],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/finfo-buffer-context-php-version.php'], [
			['Passing the $context parameter to finfo_buffer() is deprecated in PHP 8.5.', 14, 'The parameter has no effect, remove it.'],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/finfo-buffer-context.php', __DIR__ . '/data/finfo-buffer-context.php.fixed');
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80400-80500.neon',
		];
	}

}
