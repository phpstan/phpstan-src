<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MultiFixRule>
 */
class MultiFixRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MultiFixRule(self::getContainer()->getService('defaultAnalysisParser'));
	}

	public function testFixInFileOfError(): void
	{
		$this->fix(__DIR__ . '/data/multi-fix/a.php', __DIR__ . '/data/multi-fix/a.php.fixed');
	}

	public function testFixInOtherFile(): void
	{
		$this->fixOtherFile(__DIR__ . '/data/multi-fix/a.php', __DIR__ . '/data/multi-fix/other.php', __DIR__ . '/data/multi-fix/other.php.fixed');
	}

	public function testAllOrNothing(): void
	{
		$errors = $this->gatherAnalyserErrors([__DIR__ . '/data/multi-fix/all-or-nothing.php']);
		$this->assertCount(1, $errors);
		$this->assertSame([], $errors[0]->getFixedErrorDiffs());
	}

}
