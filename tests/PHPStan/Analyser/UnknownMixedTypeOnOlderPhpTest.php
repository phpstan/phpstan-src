<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Methods\ExistingClassesInTypehintsRule;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<ExistingClassesInTypehintsRule>
 */
class UnknownMixedTypeOnOlderPhpTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return self::getContainer()->getByType(ExistingClassesInTypehintsRule::class);
	}

	public function testMixedUnknownType(): void
	{
		$this->analyse([__DIR__ . '/data/unknown-mixed-type.php'], [
			[
				'Parameter $m of method UnknownMixedType\Foo::doFoo() has invalid type UnknownMixedType\mixed.',
				8,
			],
			[
				'Method UnknownMixedType\Foo::doFoo() has invalid return type UnknownMixedType\mixed.',
				8,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/unknown-mixed-type.neon',
		]);
	}

}
