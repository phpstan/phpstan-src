<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
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
use PHPStan\Node\IssetExpr;
use PHPStan\Type\Type;

/**
 * IssetExpr is a certainty marker IssetHandler wraps around the isset-tested
 * expression so a type specification can reduce that expression's existence
 * certainty (to maybe / unset) instead of narrowing its type. The specifications
 * carrying it read only its certainty, never its type - so the marker just
 * reports its inner expression's type, which lets it be priced like any other
 * node rather than being a special case in the resolution paths.
 *
 * @implements ExprHandler<IssetExpr>
 */
#[AutowiredService]
final class IssetExprHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof IssetExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// because this is a virtual node handler, the caller will only be interested
		// in the type - we don't process the inner expr, just report its type

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: static fn (bool $nativeTypesPromoted): Type => $nodeScopeResolver->readScopeStateOrSyntheticType($expr->getExpr(), $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
