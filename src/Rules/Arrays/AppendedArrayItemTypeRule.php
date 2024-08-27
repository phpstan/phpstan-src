<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @deprecated Replaced by PHPStan\Rules\Properties\TypesAssignedToPropertiesRule
 * @implements Rule<Node\Expr>
 */
final class AppendedArrayItemTypeRule implements Rule
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
			&& !$node instanceof AssignRef
		) {
			return [];
		}

		if (!($node->var instanceof ArrayDimFetch)) {
			return [];
		}

		if (
			!$node->var->var instanceof Node\Expr\PropertyFetch
			&& !$node->var->var instanceof Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node->var->var, $scope);
		if ($propertyReflection === null) {
			return [];
		}

		$assignedToType = $propertyReflection->getWritableType();
		if (!$assignedToType->isArray()->yes()) {
			return [];
		}

		if ($node instanceof Assign || $node instanceof AssignRef) {
			$assignedValueType = $scope->getType($node->expr);
		} else {
			$assignedValueType = $scope->getType($node);
		}

		$itemType = $assignedToType->getIterableValueType();
		$accepts = $this->ruleLevelHelper->acceptsWithReason($itemType, $assignedValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($itemType, $assignedValueType);
			return [
				RuleErrorBuilder::message(sprintf(
					'Array (%s) does not accept %s.',
					$assignedToType->describe($verbosityLevel),
					$assignedValueType->describe($verbosityLevel),
				))
					->acceptsReasonsTip($accepts->reasons)
					->identifier('array.valueType')
					->build(),
			];
		}

		return [];
	}

}
