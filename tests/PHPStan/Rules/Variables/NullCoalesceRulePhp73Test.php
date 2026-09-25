<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NullCoalesceRule>
 */
class NullCoalesceRulePhp73Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullCoalesceRule(
			new IssetCheck(
				new PropertyDescriptor(),
				true,
				$this->shouldTreatPhpDocTypesAsCertain(),
			),
			self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
			true,
		);
	}

	public function testBug15297(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15297.php'], [
			[
				'Offset \'foo2\' on array{0: non-falsy-string, foo1: null, 1: null, bar1: null, 2: null, foo2: non-falsy-string, 3: non-falsy-string, bar2?: non-empty-string, ...} on left side of ?? always exists and is not nullable.',
				11,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/null-coalesce-php73.neon',
		];
	}

}
