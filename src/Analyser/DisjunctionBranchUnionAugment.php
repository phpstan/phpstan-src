<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

/**
 * The either-branch union recovery: an expression the exact merge left
 * unconstrained, but that both branch scopes narrow (through fired conditional
 * holders or sibling assignments the operands' SpecifiedTypes cannot see),
 * is narrowed to the union of its branch types. The branch types are
 * position-fixed operand-walk facts captured at compose time; whether the
 * union actually narrows anything depends on the expression's current type,
 * so those gates run against the applying scope.
 */
final class DisjunctionBranchUnionAugment implements DeferredSpecifiedTypesAugment
{

	/**
	 * @param list<array{Expr, Type, Type}> $candidates [target expr, left branch type, right branch type]
	 */
	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private array $candidates,
	)
	{
	}

	public function evaluate(MutatingScope $scope): ?SpecifiedTypes
	{
		$result = null;
		foreach ($this->candidates as [$targetExpr, $leftType, $rightType]) {
			if (!$scope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}

			// the guard above pins the target as tracked on the applying scope
			$originalType = $this->nodeScopeResolver->requireScopeStateType($targetExpr, $scope);
			// re-pinning eagerly priced branch forms of a template-typed subject
			// stacks the template inside its own bound (`T of T of ...` - the
			// pin intersects with the declared template); its narrowing already
			// flows through the operands' exact merge
			if (TypeUtils::containsTemplateType($originalType)) {
				continue;
			}
			if ($leftType->equals($originalType) || !$originalType->isSuperTypeOf($leftType)->yes()) {
				continue;
			}

			if ($rightType->equals($originalType) || !$originalType->isSuperTypeOf($rightType)->yes()) {
				continue;
			}

			$unionType = TypeCombinator::union($leftType, $rightType);
			// a union that covers the whole original type gains no narrowing -
			// pinning it would only stack a redundant intersection on the
			// expression (e.g. re-wrapping a template type in its own bound)
			if ($unionType->isSuperTypeOf($originalType)->yes()) {
				continue;
			}

			$created = $this->defaultNarrowingHelper->createForSubject($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope);
			$result = $result === null ? $created : $result->unionWith($created);
		}

		return $result;
	}

}
