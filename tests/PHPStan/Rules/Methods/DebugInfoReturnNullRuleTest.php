<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<DebugInfoReturnNullRule>
 */
#[RequiresPhp('>= 8.2.0')]
class DebugInfoReturnNullRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80500;

	protected function getRule(): Rule
	{
		return new DebugInfoReturnNullRule(new PhpVersion($this->phpVersionId));
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

	public function testBeforePhp85(): void
	{
		$this->phpVersionId = 80400;
		$this->analyse([__DIR__ . '/data/debug-info-return-null.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/debug-info-return-null.php', __DIR__ . '/data/debug-info-return-null.php.fixed');
	}

}
