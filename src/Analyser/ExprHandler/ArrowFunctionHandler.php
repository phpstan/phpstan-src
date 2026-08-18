<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * @implements ExprHandler<ArrowFunction>
 */
#[AutowiredService]
final class ArrowFunctionHandler implements ExprHandler
{

	public function __construct(
		private ClosureTypeResolver $closureTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrowFunction;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$arrowFunctionResult = $nodeScopeResolver->processArrowFunctionNode($stmt, $expr, $scope, $storage, $nodeCallback, null);
		$result = $arrowFunctionResult->getExpressionResult();

		// A plain typeCallback recursing through getClosureType() would re-walk
		// the body each getType() ask before the cache populates and hang;
		// ExpressionResult excludes closures from its tracked-type early return.
		// Compute the ClosureType once here and store it as an eager value.
		//
		// Both flavours are built from the arrow function body the single walk in
		// processArrowFunctionNode() already covered, without a second walk: the
		// native flavour reads the body expression's stored native types off the
		// same arrowScope (an arrow's native return type is its body's native type).
		$arrowScope = $arrowFunctionResult->getArrowFunctionScope();
		$type = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowScope,
			$arrowFunctionResult->getClosureTypeThrowPoints(),
			$arrowFunctionResult->getClosureTypeImpurePoints(),
			$arrowFunctionResult->getInvalidateExpressions(),
			storage: $storage,
		);
		$nativeType = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
			$scope,
			$expr,
			$arrowScope,
			$arrowFunctionResult->getClosureTypeThrowPoints(),
			$arrowFunctionResult->getClosureTypeImpurePoints(),
			$arrowFunctionResult->getInvalidateExpressions(),
			native: true,
			storage: $storage,
		);

		return $this->expressionResultFactory->create(
			$result->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			specifyTypesCallback: fn (TypeSpecifierContext $c, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $c),
			type: $type,
			nativeType: $nativeType,
			typeCallback: null,
		);
	}

}
