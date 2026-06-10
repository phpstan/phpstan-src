<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<UnaryMinus>
 */
#[AutowiredService]
final class UnaryMinusHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof UnaryMinus;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());

		// the InitializerExprTypeResolver callback asks the child result first
		// (NEW_WORLD.md paragraph 3.12); anything else takes the guarded bridge
		$typeCallback = function (Expr $e, MutatingScope $s) use ($exprResult): Type {
			if (!$e instanceof UnaryMinus) {
				throw new ShouldNotHappenException();
			}

			return $this->initializerExprTypeResolver->getUnaryMinusType($e->expr, static function (Expr $inner) use ($e, $exprResult, $s): Type {
				if ($inner === $e->expr) {
					return $exprResult->getTypeForScope($s);
				}

				return $s->getType($inner);
			});
		};

		return new ExpressionResult(
			$exprResult->getScope(),
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getUnaryMinusType($expr->expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
