<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FixOtherFileRule>
 */
class FixOtherFileRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FixOtherFileRule(self::getContainer()->getService('defaultAnalysisParser'));
	}

	public function testFixInFileOfError(): void
	{
		$this->fixOtherFile(
			__DIR__ . '/data/cross-file/a.php',
			__DIR__ . '/data/cross-file/b.php',
			__DIR__ . '/data/cross-file/b.php.fixed',
		);
	}

}
