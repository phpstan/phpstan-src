<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<FinfoBufferContextRule>
 */
#[RequiresPhp('>= 8.0.0')]
class FinfoBufferContextRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80500;

	protected function getRule(): Rule
	{
		return new FinfoBufferContextRule(self::createReflectionProvider(), new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp85(): void
	{
		$this->phpVersionId = 80400;
		$this->analyse([__DIR__ . '/data/finfo-buffer-context.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/finfo-buffer-context.php', __DIR__ . '/data/finfo-buffer-context.php.fixed');
	}

}
