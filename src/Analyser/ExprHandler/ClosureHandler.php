<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
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
 * @implements ExprHandler<Closure>
 */
#[AutowiredService]
final class ClosureHandler implements ExprHandler
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
		return $expr instanceof Closure;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$processClosureResult = $nodeScopeResolver->processClosureNode($stmt, $expr, $scope, $storage, $nodeCallback, $context, null);

		// A plain typeCallback recursing through getClosureType() would re-walk
		// the body each getType() ask before the cache populates and hang;
		// ExpressionResult excludes closures from its tracked-type early return.
		// Compute the ClosureType once here and store it as an eager value.
		//
		// The phpdoc flavour is built from the returns/yields the single body walk
		// in processClosureNode() already gathered, without a second walk.
		//
		// A closure carries no @param/@return of its own, and its native type
		// resolves the body the same way its phpdoc type does (a closure's native
		// type equals its phpdoc type - e.g. a closure returning a positive-int
		// method is Closure(): int<1, max> in both flavours). So the native
		// flavour reuses the phpdoc ClosureType - no native walk.
		$type = $this->closureTypeResolver->buildClosureTypeForClosure(
			$scope,
			$expr,
			$processClosureResult->getGatheredReturnStatements(),
			$processClosureResult->getGatheredYieldStatements(),
			$processClosureResult->getExecutionEnds(),
			$processClosureResult->getThrowPoints(),
			$processClosureResult->getClosureTypeImpurePoints(),
			$processClosureResult->getInvalidateExpressions(),
			storage: $storage,
		);
		$nativeType = $type;

		return $this->expressionResultFactory->create(
			$processClosureResult->applyByRefUseScope($processClosureResult->getScope()),
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
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
