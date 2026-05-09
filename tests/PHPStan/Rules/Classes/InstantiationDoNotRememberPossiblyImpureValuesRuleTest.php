<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<InstantiationRule>
 */
class InstantiationDoNotRememberPossiblyImpureValuesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(InstantiationRule::class);
	}

	public function testBug8579(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8579.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/doNotRememberPossiblyImpureValues.neon',
			],
		);
	}

}
