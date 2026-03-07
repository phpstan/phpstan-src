<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<InterpolatedString>
 */
#[AutowiredService]
final class InterpolatedStringHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof InterpolatedString;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		foreach ($expr->parts as $part) {
			if (!$part instanceof Expr) {
				continue;
			}
			$result = $nodeScopeResolver->processExprNode($stmt, $part, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $result->hasYield();
			$throwPoints = array_merge($throwPoints, $result->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $result->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating();
			$scope = $result->getScope();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	/**
	 * @param InterpolatedString $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$resultType = null;
		foreach ($expr->parts as $part) {
			if ($part instanceof InterpolatedStringPart) {
				$partType = new ConstantStringType($part->value);
			} else {
				$partType = $scope->getType($part)->toString();
			}
			if ($resultType === null) {
				$resultType = $partType;
				continue;
			}

			$resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
		}

		return $resultType ?? new ConstantStringType('');
	}

}
