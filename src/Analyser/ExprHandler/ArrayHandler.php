<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\LiteralArrayItem;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function count;
use function is_int;

/**
 * @implements ExprHandler<Array_>
 */
#[AutowiredService]
final class ArrayHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Array_;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$type = $this->initializerExprTypeResolver->getArrayType($expr, static fn (Expr $expr): Type => $scope->getType($expr));

		if (
			count($expr->items) === 2
			&& isset($expr->items[0], $expr->items[1])
			&& $type->isCallable()->maybe()
		) {
			$isCallableCall = new FuncCall(
				new FullyQualified('is_callable'),
				[new Arg($expr)],
			);
			if (
				$scope->hasExpressionType($isCallableCall)->yes()
				&& $scope->getType($isCallableCall)->isTrue()->yes()
			) {
				$type = TypeCombinator::intersect($type, new CallableType());
			}
		}

		return $type;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context, ?Type $overriddenType): ExpressionResult
	{
		$beforeScope = $scope;
		$itemNodes = [];
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$nextAutoIndex = 0;
		foreach ($expr->items as $arrayItem) {
			$itemNodes[] = new LiteralArrayItem($scope, $arrayItem);
			$nodeScopeResolver->callNodeCallback($nodeCallback, $arrayItem, $scope, $storage);
			$keyType = new ConstantIntegerType($nextAutoIndex);
			if ($arrayItem->key !== null) {
				$keyType = $scope->getType($arrayItem->key);
				$keyResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->key, $scope, $storage, $nodeCallback, $context->enterDeep(), null);
				$hasYield = $hasYield || $keyResult->hasYield();
				$throwPoints = array_merge($throwPoints, $keyResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $keyResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $keyResult->isAlwaysTerminating();
				$scope = $keyResult->getScope();
			}

			// an overridden array type prices each item against the type declared
			// for its key, so a nested closure is walked with the parameters the
			// extension announced instead of the declared (contravariant) ones
			$overriddenValueType = null;
			if ($overriddenType !== null && $overriddenType->hasOffsetValueType($keyType)->yes()) {
				$overriddenValueType = $overriddenType->getOffsetValueType($keyType);
			}

			if ($arrayItem->key === null) {
				$nextAutoIndex++;
			} else {
				$keyIntegers = $keyType->getConstantScalarValues();
				if (count($keyIntegers) === 1 && is_int($keyIntegers[0])) {
					$nextAutoIndex = $keyIntegers[0] + 1;
				}
			}

			$valueResult = $nodeScopeResolver->processExprNode($stmt, $arrayItem->value, $scope, $storage, $nodeCallback, $context->enterDeep(), $overriddenValueType);
			$hasYield = $hasYield || $valueResult->hasYield();
			$throwPoints = array_merge($throwPoints, $valueResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $valueResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $valueResult->isAlwaysTerminating();
			$scope = $valueResult->getScope();
		}
		$nodeScopeResolver->callNodeCallback($nodeCallback, new LiteralArrayNode($expr, $itemNodes), $scope, $storage);

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
