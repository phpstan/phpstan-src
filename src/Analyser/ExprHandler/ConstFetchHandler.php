<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ConstantResolver;
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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function strtolower;

/**
 * @implements ExprHandler<ConstFetch>
 */
#[AutowiredService]
final class ConstFetchHandler implements ExprHandler
{

	public function __construct(
		private ConstantResolver $constantResolver,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ConstFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$nodeScopeResolver->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);

		return new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			expr: $expr,
			typeCallback: fn (Expr $e, MutatingScope $s): Type => $this->resolveConstFetchType($s, $e),
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	/**
	 * New-world copy of resolveType(): true/false/null literals, then
	 * holder-tracked runtime constants, then the ConstantResolver — all
	 * unguarded reads.
	 */
	private function resolveConstFetchType(MutatingScope $scope, Expr $expr): Type
	{
		if (!$expr instanceof ConstFetch) {
			throw new ShouldNotHappenException();
		}

		$constName = (string) $expr->name;
		$loweredConstName = strtolower($constName);
		if ($loweredConstName === 'true') {
			return new ConstantBooleanType(true);
		} elseif ($loweredConstName === 'false') {
			return new ConstantBooleanType(false);
		} elseif ($loweredConstName === 'null') {
			return new NullType();
		}

		$namespacedName = null;
		if (!$expr->name->isFullyQualified() && $scope->getNamespace() !== null) {
			$namespacedName = new FullyQualified([$scope->getNamespace(), $expr->name->toString()]);
		}
		$globalName = new FullyQualified($expr->name->toString());

		foreach ([$namespacedName, $globalName] as $name) {
			if ($name === null) {
				continue;
			}
			$constFetch = new ConstFetch($name);
			if ($scope->hasExpressionType($constFetch)->yes()) {
				return $this->constantResolver->resolveConstantType(
					$name->toString(),
					$scope->expressionTypes[$scope->getNodeKey($constFetch)]->getType(),
				);
			}
		}

		$constantType = $this->constantResolver->resolveConstant($expr->name, $scope);
		if ($constantType !== null) {
			return $constantType;
		}

		return new ErrorType();
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$constName = (string) $expr->name;
		$loweredConstName = strtolower($constName);
		if ($loweredConstName === 'true') {
			return new ConstantBooleanType(true);
		} elseif ($loweredConstName === 'false') {
			return new ConstantBooleanType(false);
		} elseif ($loweredConstName === 'null') {
			return new NullType();
		}

		$namespacedName = null;
		if (!$expr->name->isFullyQualified() && $scope->getNamespace() !== null) {
			$namespacedName = new FullyQualified([$scope->getNamespace(), $expr->name->toString()]);
		}
		$globalName = new FullyQualified($expr->name->toString());

		foreach ([$namespacedName, $globalName] as $name) {
			if ($name === null) {
				continue;
			}
			$constFetch = new ConstFetch($name);
			if ($scope->hasExpressionType($constFetch)->yes()) {
				return $this->constantResolver->resolveConstantType(
					$name->toString(),
					$scope->expressionTypes[$scope->getNodeKey($constFetch)]->getType(),
				);
			}
		}

		$constantType = $this->constantResolver->resolveConstant($expr->name, $scope);
		if ($constantType !== null) {
			return $constantType;
		}

		return new ErrorType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
