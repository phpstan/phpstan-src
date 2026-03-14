<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
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
		private ExpressionResultFactory $expressionResultFactory,
		private NodeScopeResolver $nodeScopeResolver,
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

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$ternaryCondResult = $nodeScopeResolver->processExprNode($stmt, $expr->cond, $scope, $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = $ternaryCondResult->getThrowPoints();
		$impurePoints = $ternaryCondResult->getImpurePoints();
		$ifTrueScope = $ternaryCondResult->getTruthyScope();
		$ifFalseScope = $ternaryCondResult->getFalseyScope();
		if ($expr->if === null) {
			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$ifFalseScope = $elseResult->getScope();
			$ifResult = null;
		} else {
			$ifResult = $nodeScopeResolver->processExprNode($stmt, $expr->if, $ifTrueScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $ifResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $ifResult->getImpurePoints());
			$ifTrueScope = $ifResult->getScope();

			$elseResult = $nodeScopeResolver->processExprNode($stmt, $expr->else, $ifFalseScope, $storage, $nodeCallback, $context);
			$throwPoints = array_merge($throwPoints, $elseResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $elseResult->getImpurePoints());
			$ifFalseScope = $elseResult->getScope();
		}

		$ifTrueType = $ifResult !== null ? $ifResult->getType() : null;
		$ifFalseType = $elseResult->getType();

		if ($ternaryCondResult->getType()->toBoolean()->isTrue()->yes()) {
			$finalScope = $ifTrueScope;
		} elseif ($ternaryCondResult->getType()->toBoolean()->isFalse()->yes()) {
			$finalScope = $ifFalseScope;
		} else {
			if ($ifTrueType instanceof NeverType && $ifTrueType->isExplicit()) {
				$finalScope = $ifFalseScope;
			} elseif ($ifFalseType instanceof NeverType && $ifFalseType->isExplicit()) {
				$finalScope = $ifTrueScope;
			} else {
				$finalScope = $ifTrueScope->mergeWith($ifFalseScope);
			}
		}

		return $this->expressionResultFactory->create(
			$expr,
			$finalScope,
			typeCallback: static function (Expr $uninteresting, MutatingScope $scope) use ($expr, $ternaryCondResult, $ifResult, $elseResult): Type {
				$booleanCondType = $ternaryCondResult->getTypeForScope($scope)->toBoolean();

				if ($expr->if === null) {
					if ($booleanCondType->isTrue()->yes()) {
						return $ternaryCondResult->getTypeForScope($scope);
					}
					if ($booleanCondType->isFalse()->yes()) {
						return $elseResult->getTypeForScope($scope);
					}

					return TypeCombinator::union(
						TypeCombinator::removeFalsey($ternaryCondResult->getTypeForScope($scope)),
						$elseResult->getTypeForScope($scope),
					);
				}

				if ($booleanCondType->isTrue()->yes()) {
					return $ifResult->getTypeForScope($scope);
				}
				if ($booleanCondType->isFalse()->yes()) {
					return $elseResult->getTypeForScope($scope);
				}

				return TypeCombinator::union(
					$ifResult->getTypeForScope($scope),
					$elseResult->getTypeForScope($scope),
				);
			},
			hasYield: $ternaryCondResult->hasYield(),
			isAlwaysTerminating: $ternaryCondResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $finalScope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $finalScope->filterByFalseyValue($expr),
		);
	}

}
