<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Analyser\SuperglobalTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SuperglobalAssignTypeRule>
 */
class SuperglobalAssignTypeRuleWithoutTypesTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SuperglobalAssignTypeRule(
			self::getContainer()->getByType(SuperglobalTypeResolver::class),
			self::getContainer()->getByType(RuleLevelHelper::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/superglobal-assign-type.php'], []);
	}

}
