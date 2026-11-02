<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[RegisteredRule(level: 3)]
final class OffsetAccessValueAssignmentRule implements MultipleNodeTypesRule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeTypes(): array
	{
		return [Assign::class, AssignOp::class, Expr\AssignRef::class];
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
			&& !$node instanceof Expr\AssignRef
		) {
			return [];
		}

		if (!$node->var instanceof Expr\ArrayDimFetch) {
			return [];
		}

		$arrayDimFetch = $node->var;
		$varType = $scope->getType($arrayDimFetch->var);
		if ($varType->isObject()->no()) {
			return [];
		}

		if ($node instanceof Assign || $node instanceof Expr\AssignRef) {
			$assignedValueType = $scope->getType($node->expr);
		} else {
			$assignedValueType = $scope->getType($node);
		}

		// The offset type lets method-level template types of offsetSet() (e.g. `@param T[TOffset] $value`)
		// resolve against the written key. An offset that is not accepted is reported by OffsetAccessAssignmentRule,
		// so only the value is checked here.
		$dimType = $arrayDimFetch->dim !== null ? $scope->getType($arrayDimFetch->dim) : null;
		$acceptsValue = static function (Type $varType) use ($dimType, $assignedValueType): bool {
			if ($dimType !== null) {
				if (!$varType->setOffsetValueType($dimType, $assignedValueType) instanceof ErrorType) {
					return true;
				}

				// The write is rejected. If the offset alone is accepted, the value is what is rejected.
				if (!$varType->setOffsetValueType($dimType, new MixedType()) instanceof ErrorType) {
					return false;
				}
			}

			return !$varType->setOffsetValueType(new MixedType(), $assignedValueType) instanceof ErrorType;
		};

		$arrayTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayDimFetch->var,
			'',
			$acceptsValue,
		);
		$arrayType = $arrayTypeResult->getType();
		if ($arrayType instanceof ErrorType) {
			return [];
		}
		$isOffsetAccessible = $arrayType->isOffsetAccessible();
		if (!$isOffsetAccessible->yes()) {
			return [];
		}
		if ($acceptsValue($arrayType)) {
			return [];
		}

		$originalArrayType = $scope->getType($arrayDimFetch->var);

		return [
			RuleErrorBuilder::message(sprintf(
				'%s does not accept %s.',
				$originalArrayType->describe(VerbosityLevel::value()),
				$assignedValueType->describe(VerbosityLevel::typeOnly()),
			))->identifier('offsetAssign.valueType')->build(),
		];
	}

}
