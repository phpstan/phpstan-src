<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class TypesAssignedToPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		PropertyDescriptor $propertyDescriptor,
		PropertyReflectionFinder $propertyReflectionFinder
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->propertyDescriptor = $propertyDescriptor;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\Assign
			&& !$node instanceof Node\Expr\AssignOp
		) {
			return [];
		}

		if (
			!($node->var instanceof Node\Expr\PropertyFetch)
			&& !($node->var instanceof Node\Expr\StaticPropertyFetch)
		) {
			return [];
		}

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($node->var, $scope);
		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			$propertyType = $propertyReflection->getWritableType();

			$overwriteType = $propertyReflection->getOverwriteType();
			if ($overwriteType instanceof \PHPStan\Type\Type) {
				$assignedValueType = $overwriteType;
			} elseif ($node instanceof Node\Expr\Assign) {
				$assignedValueType = $scope->getType($node->expr);
			} else {
				$assignedValueType = $scope->getType($node);
			}

			if ($this->ruleLevelHelper->accepts($propertyType, $assignedValueType, $scope->isDeclareStrictTypes())) {
				continue;
			}

			/** @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch */
			$propertyFetch = $node->var;
			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $propertyFetch);
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType);

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'%s (%s) does not accept %s.',
					$propertyDescription,
					$propertyType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel)
				)
			)->build();
		}

		return $errors;
	}

}
