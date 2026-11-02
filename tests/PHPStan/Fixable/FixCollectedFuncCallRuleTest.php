<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FixCollectedFuncCallRule>
 */
class FixCollectedFuncCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FixCollectedFuncCallRule(self::getContainer()->getService('currentPhpVersionRichParser'));
	}

	protected function getCollectors(): array
	{
		return [new FooFuncCallCollector()];
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/collected-func-call.php', __DIR__ . '/data/collected-func-call.php.fixed');
	}

}
