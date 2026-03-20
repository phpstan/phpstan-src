<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class CollectedDataEmitterTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new CollectedDataEmitterRule(),
			new DummyCollectorRule(),
		]);
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
