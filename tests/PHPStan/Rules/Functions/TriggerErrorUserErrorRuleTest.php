<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TriggerErrorUserErrorRule>
 */
#[RequiresPhp('>= 8.0.0')]
class TriggerErrorUserErrorRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80400;

	protected function getRule(): Rule
	{
		return new TriggerErrorUserErrorRule(self::createReflectionProvider(), new PhpVersion($this->phpVersionId));
	}

	public function testRule(): void
	{
		$tip = 'Throw an exception or call exit() with a string message instead.';
		$this->analyse([__DIR__ . '/data/trigger-error-user-error.php'], [
			['Passing E_USER_ERROR to trigger_error() is deprecated in PHP 8.4.', 8, $tip],
			['Passing E_USER_ERROR to trigger_error() is deprecated in PHP 8.4.', 11, $tip],
			['Passing E_USER_ERROR to trigger_error() is deprecated in PHP 8.4.', 14, $tip],
			['Passing E_USER_ERROR to user_error() is deprecated in PHP 8.4.', 17, $tip],
		]);
	}

	public function testBeforePhp84(): void
	{
		$this->phpVersionId = 80300;
		$this->analyse([__DIR__ . '/data/trigger-error-user-error.php'], []);
	}

}
