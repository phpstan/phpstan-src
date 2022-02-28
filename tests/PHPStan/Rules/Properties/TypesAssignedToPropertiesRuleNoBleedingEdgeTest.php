<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TypesAssignedToPropertiesRule>
 */
class TypesAssignedToPropertiesRuleNoBleedingEdgeTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		return new TypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed), new PropertyDescriptor(), new PropertyReflectionFinder());
	}

	public function testGenericObjectWithUnspecifiedTemplateTypes(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generic-object-unspecified-template-types.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		// no bleeding edge
		return [];
	}

}
