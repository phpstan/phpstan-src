<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
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
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_merge;
use function strtolower;

/**
 * @implements ExprHandler<Instanceof_>
 */
#[AutowiredService]
final class InstanceofHandler implements ExprHandler
{

	public function __construct(private TypeSpecifier $typeSpecifier)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Instanceof_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$hasYield = $exprResult->hasYield();
		$throwPoints = $exprResult->getThrowPoints();
		$impurePoints = $exprResult->getImpurePoints();
		$isAlwaysTerminating = $exprResult->isAlwaysTerminating();
		$scope = $exprResult->getScope();
		$classResult = null;
		if (!$expr->class instanceof Name) {
			$classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, $context->enterDeep());
			$scope = $classResult->getScope();
			$hasYield = $hasYield || $classResult->hasYield();
			$throwPoints = array_merge($throwPoints, $classResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $classResult->getImpurePoints());
			$isAlwaysTerminating = $isAlwaysTerminating || $classResult->isAlwaysTerminating();
		}

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $this->createTypeCallback($exprResult, $classResult),
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt, $exprResult, $classResult),
		);
	}

	/**
	 * @return callable(Expr, MutatingScope): Type
	 */
	private function createTypeCallback(ExpressionResult $exprResult, ?ExpressionResult $classResult): callable
	{
		return static function (Expr $e, MutatingScope $s) use ($exprResult, $classResult): Type {
			if (!$e instanceof Instanceof_) {
				throw new ShouldNotHappenException();
			}

			$expressionType = $exprResult->getTypeForScope($s);
			if (
				$s->isInTrait()
				&& TypeUtils::findThisType($expressionType) !== null
			) {
				return new BooleanType();
			}
			if ($expressionType instanceof NeverType) {
				return new ConstantBooleanType(false);
			}

			$uncertainty = false;

			if ($e->class instanceof Name) {
				$unresolvedClassName = $e->class->toString();
				if (
					strtolower($unresolvedClassName) === 'static'
					&& $s->isInClass()
				) {
					$classType = new StaticType($s->getClassReflection());
				} else {
					$className = $s->resolveName($e->class);
					$classType = new ObjectType($className);
				}
			} else {
				if ($classResult === null) {
					throw new ShouldNotHappenException();
				}
				$result = $classResult->getTypeForScope($s)->toObjectTypeForInstanceofCheck();
				$classType = $result->type;
				$uncertainty = $result->uncertainty;
			}

			if ($classType->isSuperTypeOf(new MixedType())->yes()) {
				return new BooleanType();
			}

			$isSuperType = $classType->isSuperTypeOf($expressionType);

			if ($isSuperType->no()) {
				return new ConstantBooleanType(false);
			} elseif ($isSuperType->yes() && !$uncertainty) {
				return new ConstantBooleanType(true);
			}

			return new BooleanType();
		};
	}

	/**
	 * New-world copy of specifyTypes(): TypeSpecifier::create() resolves its
	 * null/purity gates through an adapter seeded with the target and class
	 * results (the FuncCall self-seeding precedent).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ExpressionResult $exprResult, ?ExpressionResult $classResult): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt, $exprResult, $classResult): SpecifiedTypes {
			if (!$e instanceof Instanceof_) {
				throw new ShouldNotHappenException();
			}

			$exprNode = $e->expr;
			$exprResults = [$s->getNodeKey($exprNode) => $exprResult];
			if ($classResult !== null && $e->class instanceof Expr) {
				$exprResults[$s->getNodeKey($e->class)] = $classResult;
			}
			$adapterScope = $s->toResultAwareScope($exprResults, $nodeScopeResolver, $stmt, new ExpressionResultStorage());

			if ($e->class instanceof Name) {
				$className = (string) $e->class;
				$lowercasedClassName = strtolower($className);
				if ($lowercasedClassName === 'self' && $s->isInClass()) {
					$type = new ObjectType($s->getClassReflection()->getName());
				} elseif ($lowercasedClassName === 'static' && $s->isInClass()) {
					$type = new StaticType($s->getClassReflection());
				} elseif ($lowercasedClassName === 'parent') {
					if (
						$s->isInClass()
						&& $s->getClassReflection()->getParentClass() !== null
					) {
						$type = new ObjectType($s->getClassReflection()->getParentClass()->getName());
					} else {
						$type = new NonexistentParentClassType();
					}
				} else {
					$type = new ObjectType($className);
				}
				return $this->typeSpecifier->create($exprNode, $type, $ctx, $adapterScope)->setRootExpr($e);
			}

			if ($classResult === null) {
				throw new ShouldNotHappenException();
			}
			$result = $classResult->getTypeForScope($s)->toObjectTypeForInstanceofCheck();
			$type = $result->type;
			$uncertainty = $result->uncertainty;

			if (!$type->isSuperTypeOf(new MixedType())->yes()) {
				if ($ctx->true()) {
					$type = TypeCombinator::intersect(
						$type,
						new ObjectWithoutClassType(),
					);
					return $this->typeSpecifier->create($exprNode, $type, $ctx, $adapterScope)->setRootExpr($e);
				} elseif ($ctx->false() && !$uncertainty) {
					$exprType = $exprResult->getTypeForScope($s);
					if (!$type->isSuperTypeOf($exprType)->yes()) {
						return $this->typeSpecifier->create($exprNode, $type, $ctx, $adapterScope)->setRootExpr($e);
					}
				}
			}
			if ($ctx->true()) {
				return $this->typeSpecifier->create($exprNode, new ObjectWithoutClassType(), $ctx, $adapterScope)->setRootExpr($exprNode);
			}

			return (new SpecifiedTypes([], []))->setRootExpr($e);
		};
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$expressionType = $scope->getType($expr->expr);
		if (
			$scope->isInTrait()
			&& TypeUtils::findThisType($expressionType) !== null
		) {
			return new BooleanType();
		}
		if ($expressionType instanceof NeverType) {
			return new ConstantBooleanType(false);
		}

		$uncertainty = false;

		if ($expr->class instanceof Name) {
			$unresolvedClassName = $expr->class->toString();
			if (
				strtolower($unresolvedClassName) === 'static'
				&& $scope->isInClass()
			) {
				$classType = new StaticType($scope->getClassReflection());
			} else {
				$className = $scope->resolveName($expr->class);
				$classType = new ObjectType($className);
			}
		} else {
			$result = $scope->getType($expr->class)->toObjectTypeForInstanceofCheck();
			$classType = $result->type;
			$uncertainty = $result->uncertainty;
		}

		if ($classType->isSuperTypeOf(new MixedType())->yes()) {
			return new BooleanType();
		}

		$isSuperType = $classType->isSuperTypeOf($expressionType);

		if ($isSuperType->no()) {
			return new ConstantBooleanType(false);
		} elseif ($isSuperType->yes() && !$uncertainty) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$exprNode = $expr->expr;
		if ($expr->class instanceof Name) {
			$className = (string) $expr->class;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'self' && $scope->isInClass()) {
				$type = new ObjectType($scope->getClassReflection()->getName());
			} elseif ($lowercasedClassName === 'static' && $scope->isInClass()) {
				$type = new StaticType($scope->getClassReflection());
			} elseif ($lowercasedClassName === 'parent') {
				if (
					$scope->isInClass()
					&& $scope->getClassReflection()->getParentClass() !== null
				) {
					$type = new ObjectType($scope->getClassReflection()->getParentClass()->getName());
				} else {
					$type = new NonexistentParentClassType();
				}
			} else {
				$type = new ObjectType($className);
			}
			return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
		}

		$result = $scope->getType($expr->class)->toObjectTypeForInstanceofCheck();
		$type = $result->type;
		$uncertainty = $result->uncertainty;

		if (!$type->isSuperTypeOf(new MixedType())->yes()) {
			if ($context->true()) {
				$type = TypeCombinator::intersect(
					$type,
					new ObjectWithoutClassType(),
				);
				return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
			} elseif ($context->false() && !$uncertainty) {
				$exprType = $scope->getType($expr->expr);
				if (!$type->isSuperTypeOf($exprType)->yes()) {
					return $typeSpecifier->create($exprNode, $type, $context, $scope)->setRootExpr($expr);
				}
			}
		}
		if ($context->true()) {
			return $typeSpecifier->create($exprNode, new ObjectWithoutClassType(), $context, $scope)->setRootExpr($exprNode);
		}

		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
