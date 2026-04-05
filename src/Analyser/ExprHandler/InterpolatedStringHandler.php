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
use PHPStan\Analyser\ExprHandler\Helper\ToStringThrowPointHelper;
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
		private ToStringThrowPointHelper $toStringThrowPointHelper,
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
			$partResult = $nodeScopeResolver->processExprNode($stmt, $part, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $hasYield || $partResult->hasYield();
			$throwPoints = array_merge($throwPoints, $partResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $partResult->getImpurePoints());

			[$toStringThrowPoints, $toStringImpurePoints] = $this->toStringThrowPointHelper->getToStringThrowAndImpurePoints($part, $scope);
			$throwPoints = array_merge($throwPoints, $toStringThrowPoints);
			$impurePoints = array_merge($impurePoints, $toStringImpurePoints);

			$isAlwaysTerminating = $isAlwaysTerminating || $partResult->isAlwaysTerminating();
			$scope = $partResult->getScope();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

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
