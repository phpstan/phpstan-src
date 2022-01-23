<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<DefineParametersRule>
 */
class DefineParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DefineParametersRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testFile(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->analyse([__DIR__ . '/data/call-to-define.php'], []);
		} else {
			$this->analyse([__DIR__ . '/data/call-to-define.php'], [
				[
					'Argument #3 ($case_insensitive) is ignored since declaration of case-insensitive constants is no longer supported.',
					3,
				],
			]);
		}
	}

}
