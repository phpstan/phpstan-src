<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<ArrayDimFetch>
 */
#[AutowiredService]
final class ArrayDimFetchHandler implements ExprHandler
{

	public function __construct(private DefaultNarrowingHelper $defaultNarrowingHelper)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrayDimFetch;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->dim === null) {
			return new NeverType();
		}

		$offsetAccessibleType = $scope->getType($expr->var);
		if (
			!$offsetAccessibleType->isArray()->yes()
			&& (new ObjectType(ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()
		) {
			return NullsafeShortCircuitingHelper::getType(
				$scope,
				$expr->var,
				$scope->getType(
					new MethodCall(
						$expr->var,
						new Identifier('offsetGet'),
						[
							new Arg($expr->dim),
						],
					),
				),
			);
		}

		$offsetType = $scope->getType($expr->dim);
		return NullsafeShortCircuitingHelper::getType(
			$scope,
			$expr->var,
			$offsetAccessibleType->getOffsetValueType($offsetType),
		);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		if ($expr->dim === null) {
			$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $varResult->getScope();

			return new ExpressionResult(
				$scope,
				hasYield: $varResult->hasYield(),
				isAlwaysTerminating: $varResult->isAlwaysTerminating(),
				throwPoints: $varResult->getThrowPoints(),
				impurePoints: $varResult->getImpurePoints(),
				expr: $expr,
				typeCallback: static fn (): Type => new NeverType(),
				specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
			);
		}

		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeep());
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $dimResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = array_merge($dimResult->getThrowPoints(), $varResult->getThrowPoints());
		$impurePoints = array_merge($dimResult->getImpurePoints(), $varResult->getImpurePoints());
		$scope = $varResult->getScope();

		$varType = $varResult->getType();
		if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
			$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
				$stmt,
				new MethodCall(new TypeExpr($varType), 'offsetGet'),
				$scope,
				$storage,
				new NoopNodeCallback(),
				$context,
			)->getThrowPoints());
		}

		// a nullsafe var that can be null short-circuits this fetch too; its
		// handler already produced the null-union — propagate one level, no
		// recursive chain walking (NEW_WORLD.md §3.10)
		$isShortcircuited = static function (Expr $e, MutatingScope $s) use ($varResult): bool {
			if (!$e instanceof ArrayDimFetch) {
				throw new ShouldNotHappenException();
			}

			return ($e->var instanceof Expr\NullsafePropertyFetch || $e->var instanceof Expr\NullsafeMethodCall)
				&& TypeCombinator::containsNull($varResult->getTypeForScope($s));
		};
		$typeCallback = static function (Expr $e, MutatingScope $s) use ($varResult, $dimResult, $isShortcircuited, $nodeScopeResolver, $stmt): Type {
			if (!$e instanceof ArrayDimFetch || $e->dim === null) {
				throw new ShouldNotHappenException();
			}

			$varTypeForFetch = $varResult->getTypeForScope($s);
			if ($isShortcircuited($e, $s)) {
				$varTypeForFetch = TypeCombinator::removeNull($varTypeForFetch);
			}

			if (
				!$varTypeForFetch->isArray()->yes()
				&& (new ObjectType(ArrayAccess::class))->isSuperTypeOf($varTypeForFetch)->yes()
			) {
				// ArrayAccess: the offsetGet() synthetic, processed on demand
				// (ResultAwareScope tier 4)
				$fetchedType = $s->toResultAwareScope([], $nodeScopeResolver, $stmt, new ExpressionResultStorage())->getType(
					new MethodCall(
						new TypeExpr($varTypeForFetch),
						new Identifier('offsetGet'),
						[
							new Arg($e->dim),
						],
					),
				);
			} else {
				$fetchedType = $varTypeForFetch->getOffsetValueType($dimResult->getTypeForScope($s));
			}

			if ($isShortcircuited($e, $s)) {
				return TypeCombinator::union($fetchedType, new NullType());
			}

			return $fetchedType;
		};

		return new ExpressionResult(
			$scope,
			hasYield: $dimResult->hasYield() || $varResult->hasYield(),
			isAlwaysTerminating: $dimResult->isAlwaysTerminating() || $varResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
