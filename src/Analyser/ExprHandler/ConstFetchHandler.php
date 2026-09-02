<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function strtolower;

/**
 * @implements ExprHandler<ConstFetch>
 */
#[AutowiredService]
final class ConstFetchHandler implements ExprHandler
{

	public function __construct(
		private ConstantResolver $constantResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ConstFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: function (bool $nativeTypesPromoted) use ($expr, $scope): Type {
				$constName = (string) $expr->name;
				$loweredConstName = strtolower($constName);
				if ($loweredConstName === 'true') {
					return new ConstantBooleanType(true);
				} elseif ($loweredConstName === 'false') {
					return new ConstantBooleanType(false);
				} elseif ($loweredConstName === 'null') {
					return new NullType();
				}

				$namespacedName = null;
				if (!$expr->name->isFullyQualified() && $scope->getNamespace() !== null) {
					$namespacedName = new FullyQualified([$scope->getNamespace(), $expr->name->toString()]);
				}
				$globalName = new FullyQualified($expr->name->toString());

				foreach ([$namespacedName, $globalName] as $name) {
					if ($name === null) {
						continue;
					}
					$constFetch = new ConstFetch($name);
					if ($scope->hasExpressionType($constFetch)->yes()) {
						return $this->constantResolver->resolveConstantType(
							$name->toString(),
							$scope->expressionTypes[$scope->getNodeKey($constFetch)]->getType(),
						);
					}
				}

				$constantType = $this->constantResolver->resolveConstant($expr->name, $scope);
				if ($constantType !== null) {
					return $constantType;
				}

				return new ErrorType();
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
