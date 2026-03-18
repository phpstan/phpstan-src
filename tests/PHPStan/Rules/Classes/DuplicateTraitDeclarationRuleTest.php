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
			[
				'Cannot redeclare constant Bug14250\TraitWithDuplicateConstants::CONST1.',
				24,
			],
			[
				'Cannot redeclare constant Bug14250\TraitWithDuplicateConstants::CONST2.',
				26,
			],
			[
				'Cannot redeclare property Bug14250\TraitWithDuplicateProperties::$prop1.',
				41,
			],
			[
				'Cannot redeclare property Bug14250\TraitWithDuplicateProperties::$prop2.',
				44,
			],
			[
				'Cannot redeclare method Bug14250\TraitWithDuplicateMethods::func1().',
				59,
			],
			[
				'Cannot redeclare method Bug14250\TraitWithDuplicateMethods::Func1().',
				69,
			],
		]);
	}

	public function testDuplicatePromotedProperty(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14250-promoted-properties.php'], [
			[
				'Cannot redeclare property Bug14250PromotedProperties\TraitWithDuplicatePromotedProperties::$foo.',
				10,
			],
			[
				'Cannot redeclare property Bug14250PromotedProperties\TraitWithDuplicatePromotedProperties::$bar.',
				12,
			],
		]);
	}

}
