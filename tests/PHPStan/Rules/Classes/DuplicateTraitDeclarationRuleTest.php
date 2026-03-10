<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DuplicateTraitDeclarationRule>
 */
class DuplicateTraitDeclarationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DuplicateTraitDeclarationRule();
	}

	public function testBug14250(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14250.php'], [
			[
				'Cannot redeclare method Bug14250\MyTrait::doSomething().',
				11,
			],
		]);
	}

}
