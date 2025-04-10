<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<DefaultValueTypesAssignedToPropertiesRule>
 */
class Bug7074Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DefaultValueTypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, false, true));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7074.php'], [
			[
				'Property Bug7074\SomeModel2::$primaryKey (array<int, string>|string) does not accept default value of type array<int, float>.',
				23,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/bug-7074.neon'],
		);
	}

}
