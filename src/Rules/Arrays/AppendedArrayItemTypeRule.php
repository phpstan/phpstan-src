<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ArrayDimFetchAssignNode;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<ArrayDimFetchAssignNode>
 */
class AppendedArrayItemTypeRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private PropertyDescriptor $propertyDescriptor;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		PropertyReflectionFinder $propertyReflectionFinder,
		PropertyDescriptor $propertyDescriptor,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->propertyDescriptor = $propertyDescriptor;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return ArrayDimFetchAssignNode::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		$var = $node->getVar();
		if (
			!$var instanceof \PhpParser\Node\Expr\PropertyFetch
			&& !$var instanceof \PhpParser\Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($var, $scope);
		if ($propertyReflection === null) {
			return [];
		}

		$assignedToType = $propertyReflection->getWritableType();
		$assignedValueType = $node->getAssignedType();

		if (!$this->ruleLevelHelper->accepts($assignedToType, $assignedValueType, $scope->isDeclareStrictTypes())) {
			$propertyDescription = $this->propertyDescriptor->describePropertyByName($propertyReflection, $propertyReflection->getName());
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($assignedToType, $assignedValueType);

			return [
				RuleErrorBuilder::message(sprintf(
					'%s (%s) does not accept %s.',
					$propertyDescription,
					$assignedToType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel)
				))->build(),
			];
		}

		return [];
	}

}
