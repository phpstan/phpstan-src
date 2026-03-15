<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;
use function array_reverse;

/**
 * @implements ExprHandler<Isset_>
 */
#[AutowiredService]
final class IssetHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private NonNullabilityHelper $nonNullabilityHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Isset_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$issetResult = true;
		foreach ($expr->vars as $var) {
			$result = $scope->issetCheck($var, static function (Type $type): ?bool {
				$isNull = $type->isNull();
				if ($isNull->maybe()) {
					return null;
				}

				return !$isNull->yes();
			});
			if ($result !== null) {
				if (!$result) {
					return new ConstantBooleanType($result);
				}

				continue;
			}

			$issetResult = $result;
		}

		if ($issetResult === null) {
			return new BooleanType();
		}

		return new ConstantBooleanType($issetResult);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$nonNullabilityResults = [];
		$isAlwaysTerminating = false;
		foreach ($expr->vars as $var) {
			$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $var);
			$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $var);
			$varResult = $nodeScopeResolver->processExprNode($stmt, $var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $varResult->getScope();
			$hasYield = $hasYield || $varResult->hasYield();
			$throwPoints = array_merge($throwPoints, $varResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $varResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $varResult->isAlwaysTerminating();
			$nonNullabilityResults[] = $nonNullabilityResult;

			if (!($var instanceof ArrayDimFetch)) {
				continue;
			}

			$varType = $nodeScopeResolver->processExprNode($stmt, $var->var, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), $context->enterDeep())->getType();
			if ($varType->isArray()->yes() || (new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
				continue;
			}

			$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
				$stmt,
				new MethodCall($var->var, 'offsetExists'),
				$scope,
				$storage,
				new NoopNodeCallback(),
				$context,
			)->getThrowPoints());
		}
		foreach (array_reverse($expr->vars) as $var) {
			$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $var);
		}
		foreach (array_reverse($nonNullabilityResults) as $nonNullabilityResult) {
			$scope = $this->nonNullabilityHelper->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
		}

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: static function (Expr $expr, MutatingScope $scope) use ($nodeScopeResolver, $stmt): Type {
				$typeResolver = static fn (Expr $e): Type => $nodeScopeResolver->processExprNode($stmt, $e, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep())->getTypeForScope($scope);

				$issetResult = true;
				foreach ($expr->vars as $var) {
					$result = $scope->issetCheckWithResolver($var, static function (Type $type): ?bool {
						$isNull = $type->isNull();
						if ($isNull->maybe()) {
							return null;
						}

						return !$isNull->yes();
					}, $typeResolver);
					if ($result !== null) {
						if (!$result) {
							return new ConstantBooleanType($result);
						}

						continue;
					}

					$issetResult = $result;
				}

				if ($issetResult === null) {
					return new BooleanType();
				}

				return new ConstantBooleanType($issetResult);
			},
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

}
