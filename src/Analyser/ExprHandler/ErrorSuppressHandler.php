<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ErrorSuppress;
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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<ErrorSuppress>
 */
#[AutowiredService]
final class ErrorSuppressHandler implements ExprHandler
{

	public function __construct(private TypeSpecifier $typeSpecifier)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ErrorSuppress;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context);

		return new ExpressionResult(
			$exprResult->getScope(),
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $exprResult->getTruthyScope(),
			falseyScopeCallback: static fn (): MutatingScope => $exprResult->getFalseyScope(),
			expr: $expr,
			typeCallback: static fn (Expr $e, MutatingScope $s): Type => $exprResult->getTypeForScope($s),
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt, $exprResult),
		);
	}

	/**
	 * The suppressed expression's narrowing as-is; a not-yet-migrated inner
	 * takes the old-world dispatcher with an unseeded adapter (the inner must
	 * be evaluated on the ask scope, NEW_WORLD.md paragraph 3.13).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ExpressionResult $exprResult): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt, $exprResult): SpecifiedTypes {
			if (!$e instanceof ErrorSuppress) {
				throw new ShouldNotHappenException();
			}

			if ($exprResult->hasSpecifiedTypesCallback()) {
				return $exprResult->getSpecifiedTypes($s, $ctx)->setRootExpr($e);
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			return $this->typeSpecifier->specifyTypesInCondition($adapterScope, $e->expr, $ctx)->setRootExpr($e);
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->getType($expr->expr);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyTypesInCondition($scope, $expr->expr, $context)->setRootExpr($expr);
	}

}
