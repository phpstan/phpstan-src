<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NotAnalysedTraitRule>
 */
class NotAnalysedTraitRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NotAnalysedTraitRule();
	}

	protected function getCollectors(): array
	{
		return [
			new TraitDeclarationCollector(),
			new TraitUseCollector(),
		];
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/not-analysed-trait.php'], [
			[
				'Trait NotAnalysedTrait\Bar is used zero times and is not analysed.',
				10,
				'See: https://phpstan.org/blog/how-phpstan-analyses-traits',
			],
		]);
	}

}
