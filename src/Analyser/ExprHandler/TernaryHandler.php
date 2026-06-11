<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<Ternary>
 */
#[AutowiredService]
final class TernaryHandler implements ExprHandler
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Ternary;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$condResult = $this->nodeScopeResolver->processExprNode(new Stmt\Expression($expr->cond), $expr->cond, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
		if ($expr->if === null) {
			$conditionType = $scope->getType($expr->cond);
			$booleanConditionType = $conditionType->toBoolean();
			if ($booleanConditionType->isTrue()->yes()) {
				return $condResult->getTruthyScope()->getType($expr->cond);
			}

			if ($booleanConditionType->isFalse()->yes()) {
				return $condResult->getFalseyScope()->getType($expr->else);
			}

			return TypeCombinator::union(
				TypeCombinator::removeFalsey($condResult->getTruthyScope()->getType($expr->cond)),
				$condResult->getFalseyScope()->getType($expr->else),
			);
		}

		$booleanConditionType = $scope->getType($expr->cond)->toBoolean();
		if ($booleanConditionType->isTrue()->yes()) {
			return $condResult->getTruthyScope()->getType($expr->if);
		}

		if ($booleanConditionType->isFalse()->yes()) {
			return $condResult->getFalseyScope()->getType($expr->else);
		}

		return TypeCombinator::union(
			$condResult->getTruthyScope()->getType($expr->if),
			$condResult->getFalseyScope()->getType($expr->else),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($expr->cond instanceof Ternary || $context->null()) {
			return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
		}

		if ($expr->if !== null) {
			$conditionExpr = new BooleanOr(
				new BooleanAnd($expr->cond, $expr->if),
				new BooleanAnd(new Expr\BooleanNot($expr->cond), $expr->else),
			);
		} else {
			$conditionExpr = new BooleanOr(
				$expr->cond,
				new BooleanAnd(new Expr\BooleanNot($expr->cond), $expr->else),
			);
		}

		return $typeSpecifier->specifyTypesInCondition($scope, $conditionExpr, $context)->setRootExpr($expr);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$ternaryCondResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = $ternaryCondResult->getThrowPoints();
		$impurePoints = $ternaryCondResult->getImpurePoints();
		$hasYield = $ternaryCondResult->hasYield();
		$ifTrueScope = $ternaryCondResult->getTruthyScope();
		$ifFalseScope = $ternaryCondResult->getFalseyScope();
		$ifTrueType = null;

		if ($expr->if === null) {
			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$hasYield = $hasYield || $elseResult->hasYield();
			$ifFalseScope = $elseResult->getScope();
		} else {
			$ifResult = $nodeScopeResolver->processExprNode($stmt, $expr->if, $ifTrueScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $ifResult->getImpurePoints());
			$hasYield = $hasYield || $ifResult->hasYield();
			$ifTrueScope = $ifResult->getScope();
			$ifTrueType = $ifTrueScope->getType($expr->if);

			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$hasYield = $hasYield || $elseResult->hasYield();
			$ifFalseScope = $elseResult->getScope();
		}

		$condType = $scope->getType($expr->cond);
		if ($condType->isTrue()->yes()) {
			$finalScope = $ifTrueScope;
		} elseif ($condType->isFalse()->yes()) {
			$finalScope = $ifFalseScope;
		} else {
			if ($ifTrueType instanceof NeverType && $ifTrueType->isExplicit()) {
				$finalScope = $ifFalseScope;
			} else {
				$ifFalseType = $ifFalseScope->getType($expr->else);

				if ($ifFalseType instanceof NeverType && $ifFalseType->isExplicit()) {
					$finalScope = $ifTrueScope;
				} else {
					$finalScope = $ifTrueScope->mergeWith($ifFalseScope);
				}
			}
		}

		return $this->expressionResultFactory->create(
			$finalScope,
			hasYield: $hasYield,
			isAlwaysTerminating: $ternaryCondResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $finalScope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $finalScope->filterByFalseyValue($expr),
		);
	}

}
