<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DummyCollectorRule>
 */
class DummyCollectorRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DummyCollectorRule();
	}

	protected function getCollectors(): array
	{
		return [
			new DummyCollector(),
		];
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/dummy-collector.php'], [
			[
				'2× doFoo, 2× doBar',
				5,
			],
		]);
	}

}
