<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class AppendedArrayItemTypeRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		PropertyReflectionFinder $propertyReflectionFinder,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->propertyReflectionFinder = $propertyReflectionFinder;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
		) {
			return [];
		}

		if (!($node->var instanceof ArrayDimFetch)) {
			return [];
		}

		if (
			!$node->var->var instanceof \PhpParser\Node\Expr\PropertyFetch
			&& !$node->var->var instanceof \PhpParser\Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($node->var->var, $scope);
		if (count($propertyReflections) === 0) {
			return [];
		}

		$errors = [];
		foreach ($propertyReflections as $propertyReflection) {
			$assignedToType = $propertyReflection->getWritableType();
			if (!($assignedToType instanceof ArrayType)) {
				continue;
			}

			if ($node instanceof Assign) {
				$assignedValueType = $scope->getType($node->expr);
			} else {
				$assignedValueType = $scope->getType($node);
			}

			$itemType = $assignedToType->getItemType();
			if ($this->ruleLevelHelper->accepts($itemType, $assignedValueType, $scope->isDeclareStrictTypes())) {
				continue;
			}

			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($itemType);

			$errors[] = RuleErrorBuilder::message(
				sprintf(
					'Array (%s) does not accept %s.',
					$assignedToType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel)
				)
			)->build();
		}

		return $errors;
	}

}
