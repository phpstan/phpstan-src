<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;

/**
 * @extends \PHPStan\Testing\RuleTestCase<DefineParametersRule>
 */
class DefineParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new DefineParametersRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testFile(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/call-to-define.php'], [
			[
				'Argument #3 ($case_insensitive) is ignored since declaration of case-insensitive constants is no longer supported.',
				3,
			],
		]);
	}

}
