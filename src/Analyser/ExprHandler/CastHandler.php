<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
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
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<Cast>
 */
#[AutowiredService]
final class CastHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private TypeSpecifier $typeSpecifier,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Cast && !$expr instanceof Cast\String_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $exprResult->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			expr: $expr,
			typeCallback: $this->createTypeCallback($exprResult),
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt),
		);
	}

	/**
	 * @return callable(Expr, MutatingScope): Type
	 */
	private function createTypeCallback(ExpressionResult $exprResult): callable
	{
		return function (Expr $e, MutatingScope $s) use ($exprResult): Type {
			if (!$e instanceof Cast) {
				throw new ShouldNotHappenException();
			}

			if ($e instanceof Cast\Unset_) {
				return new NullType();
			}

			return $this->initializerExprTypeResolver->getCastType($e, static function (Expr $inner) use ($e, $exprResult, $s): Type {
				if ($inner === $e->expr) {
					return $exprResult->getTypeForScope($s);
				}

				return $s->getType($inner);
			});
		};
	}

	/**
	 * New-world copy of specifyTypes(): the old comparison synthetics, processed
	 * through the migrated handlers on demand (ResultAwareScope tier 4,
	 * unseeded — §3.13).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt): SpecifiedTypes {
			if (!$e instanceof Cast) {
				throw new ShouldNotHappenException();
			}

			$conditionExpr = null;
			if ($e instanceof Cast\Bool_) {
				$conditionExpr = new Equal($e->expr, new ConstFetch(new FullyQualified('true')));
			} elseif ($e instanceof Cast\Int_) {
				$conditionExpr = new NotEqual($e->expr, new Int_(0));
			} elseif ($e instanceof Cast\Double) {
				$conditionExpr = new NotEqual($e->expr, new Float_(0.0));
			}

			if ($conditionExpr === null) {
				return $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx);
			}

			$adapterScope = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			return $this->typeSpecifier->specifyTypesInCondition($adapterScope, $conditionExpr, $ctx)->setRootExpr($e);
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr instanceof Cast\Unset_) {
			return new NullType();
		}

		return $this->initializerExprTypeResolver->getCastType($expr, static fn (Expr $expr): Type => $scope->getType($expr));
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($expr instanceof Cast\Bool_) {
			return $typeSpecifier->specifyTypesInCondition(
				$scope,
				new Equal($expr->expr, new ConstFetch(new FullyQualified('true'))),
				$context,
			)->setRootExpr($expr);
		}

		if ($expr instanceof Cast\Int_) {
			return $typeSpecifier->specifyTypesInCondition(
				$scope,
				new NotEqual($expr->expr, new Int_(0)),
				$context,
			)->setRootExpr($expr);
		}

		if ($expr instanceof Cast\Double) {
			return $typeSpecifier->specifyTypesInCondition(
				$scope,
				new NotEqual($expr->expr, new Float_(0.0)),
				$context,
			)->setRootExpr($expr);
		}

		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
