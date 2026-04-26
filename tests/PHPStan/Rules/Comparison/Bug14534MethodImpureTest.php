<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<StrictComparisonOfDifferentTypesRule>
 */
class Bug14534MethodImpureTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new StrictComparisonOfDifferentTypesRule(
			self::getContainer()->getByType(RicherScopeGetTypeHelper::class),
			new PossiblyImpureTipHelper(true),
			true,
			true,
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14534-method-impure.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/bug-14534-method-impure.neon'],
		);
	}

}
