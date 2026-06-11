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
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<ArrowFunction>
 */
#[AutowiredService]
final class ArrowFunctionHandler implements ExprHandler
{

	public function __construct(
		private ClosureTypeResolver $closureTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrowFunction;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$result = $nodeScopeResolver->processArrowFunctionNode($stmt, $expr, $scope, $storage, $nodeCallback, null);

		return $this->expressionResultFactory->create(
			$result->getScope(),
			beforeScope: $scope,
			expr: $expr,
			hasYield: $result->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->closureTypeResolver->getClosureType($scope, $expr);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
