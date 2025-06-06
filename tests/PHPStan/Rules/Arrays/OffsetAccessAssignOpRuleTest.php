<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OffsetAccessAssignOpRule>
 */
class OffsetAccessAssignOpRuleTest extends RuleTestCase
{

	private bool $checkUnions;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(self::createReflectionProvider(), true, false, $this->checkUnions, false, false, false, true);
		return new OffsetAccessAssignOpRule($ruleLevelHelper);
	}

	public function testRule(): void
	{
		$this->checkUnions = true;
		$this->analyse([__DIR__ . '/data/offset-access-assignop.php'], [
			[
				'Cannot assign offset \'foo\' to array|int.',
				30,
			],
		]);
	}

	public function testRuleWithoutUnions(): void
	{
		$this->checkUnions = false;
		$this->analyse([__DIR__ . '/data/offset-access-assignop.php'], []);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkUnions = true;
		$this->analyse([__DIR__ . '/data/offset-access-assignop-nullsafe.php'], []);
	}

}
