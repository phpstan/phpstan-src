<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<PropertyAssignNode>
 */
class TypesAssignedToPropertiesRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private PropertyDescriptor $propertyDescriptor,
		private PropertyReflectionFinder $propertyReflectionFinder,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($node->getPropertyFetch(), $scope);

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			$errors = array_merge($errors, $this->processSingleProperty(
				$propertyReflection,
				$node->getAssignedExpr(),
			));
		}

		return $errors;
	}

	/**
	 * @return RuleError[]
	 */
	private function processSingleProperty(
		FoundPropertyReflection $propertyReflection,
		Node\Expr $assignedExpr,
	): array
	{
		$propertyType = $propertyReflection->getWritableType();
		$scope = $propertyReflection->getScope();
		$assignedValueType = $scope->getType($assignedExpr);

		if (!$this->ruleLevelHelper->accepts($propertyType, $assignedValueType, $scope->isDeclareStrictTypes())) {
			$propertyDescription = $this->propertyDescriptor->describePropertyByName($propertyReflection, $propertyReflection->getName());
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $assignedValueType);

			return [
				RuleErrorBuilder::message(sprintf(
					'%s (%s) does not accept %s.',
					$propertyDescription,
					$propertyType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel),
				))->build(),
			];
		}

		return [];
	}

}
