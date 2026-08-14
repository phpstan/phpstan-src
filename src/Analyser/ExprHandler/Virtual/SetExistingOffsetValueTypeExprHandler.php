<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<SetExistingOffsetValueTypeExpr>
 */
#[AutowiredService]
final class SetExistingOffsetValueTypeExprHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof SetExistingOffsetValueTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// virtual node: callers only read the type, computed lazily by the
		// typeCallback. The (synthetic) sub-expressions are processed here - by
		// on-demand time their real leaves are already stored, so this reads them
		// back; the typeCallback then reads the ExpressionResults instead of
		// Scope::getType(). A null specifyTypesCallback falls back to default
		// narrowing in TypeSpecifier, matching the old specifyDefaultTypes().
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, $context);
		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->getDim(), $scope, $storage, $nodeCallback, $context);
		$valueResult = $nodeScopeResolver->processExprNode($stmt, $expr->getValue(), $scope, $storage, $nodeCallback, $context);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType())->setExistingOffsetValueType(
				($nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType()),
				($nativeTypesPromoted ? $valueResult->getNativeType() : $valueResult->getType()),
			),
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);
	}

}
