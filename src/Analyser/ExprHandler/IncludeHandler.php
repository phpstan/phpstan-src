<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;
use function in_array;

/**
 * @implements ExprHandler<Include_>
 */
#[AutowiredService]
final class IncludeHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Include_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$identifier = in_array($expr->type, [Include_::TYPE_INCLUDE, Include_::TYPE_INCLUDE_ONCE], true) ? 'include' : 'require';
		// the included file may read any variable
		$nodeScopeResolver->markAllReachingVariablesRead($exprResult->getScope());
		$scope = $exprResult->getScope()->afterExtractCall()->invalidateVolatileExpressions();

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: array_merge($exprResult->getThrowPoints(), [InternalThrowPoint::createImplicit($scope, $expr)]),
			impurePoints: array_merge($exprResult->getImpurePoints(), [new ImpurePoint($scope, $expr, $identifier, $identifier, true)]),
			typeCallback: static fn (bool $nativeTypesPromoted): Type => new MixedType(),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
