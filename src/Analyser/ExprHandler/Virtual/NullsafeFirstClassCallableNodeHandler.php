<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\NullsafeFirstClassCallableNode;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<NullsafeFirstClassCallableNode>
 */
#[AutowiredService]
final class NullsafeFirstClassCallableNodeHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof NullsafeFirstClassCallableNode;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// NullsafeFirstClassCallableNode is a syntax error, no need to process further
		return new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		// NullsafeFirstClassCallableNode is a syntax error
		return new MixedType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return new SpecifiedTypes();
	}

}
