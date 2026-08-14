<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
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

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Instanceof_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
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

		// When the class side is written as a Name (self / static / parent / a
		// resolved class name) it is lexical - it does not vary with the scope the
		// callbacks are later invoked on - so resolve the boolean-result class type
		// and the narrowing type once here. $isInTrait is likewise lexical.
		$isInTrait = $beforeScope->isInTrait();
		$nameClassType = null;
		$nameNarrowType = null;
		if ($expr->class instanceof Name) {
			if (strtolower($expr->class->toString()) === 'static' && $beforeScope->isInClass()) {
				$nameClassType = new StaticType($beforeScope->getClassReflection());
			} else {
				$nameClassType = new ObjectType($beforeScope->resolveName($expr->class));
			}

			$className = (string) $expr->class;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'self' && $beforeScope->isInClass()) {
				$nameNarrowType = new ObjectType($beforeScope->getClassReflection()->getName());
			} elseif ($lowercasedClassName === 'static' && $beforeScope->isInClass()) {
				$nameNarrowType = new StaticType($beforeScope->getClassReflection());
			} elseif ($lowercasedClassName === 'parent') {
				if (
					$beforeScope->isInClass()
					&& $beforeScope->getClassReflection()->getParentClass() !== null
				) {
					$nameNarrowType = new ObjectType($beforeScope->getClassReflection()->getParentClass()->getName());
				} else {
					$nameNarrowType = new NonexistentParentClassType();
				}
			} else {
				$nameNarrowType = new ObjectType($className);
			}
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			typeCallback: static function (bool $nativeTypesPromoted) use ($expr, $exprResult, $classResult, $isInTrait, $nameClassType): Type {
				$expressionType = $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
				if (
					$isInTrait
					&& TypeUtils::findThisType($expressionType) !== null
				) {
					return new BooleanType();
				}
				if ($expressionType instanceof NeverType) {
					return new ConstantBooleanType(false);
				}

				$uncertainty = false;

				if ($expr->class instanceof Name) {
					if ($nameClassType === null) {
						throw new ShouldNotHappenException();
					}
					$classType = $nameClassType;
				} else {
					// this branch is only reached when $expr->class is an Expr,
					// which is exactly when $classResult was set in processExpr
					if ($classResult === null) {
						throw new ShouldNotHappenException();
					}
					$classNameType = $nativeTypesPromoted ? $classResult->getNativeType() : $classResult->getType();
					$result = $classNameType->toObjectTypeForInstanceofCheck();
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
			},
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $exprResult, $classResult, $nameNarrowType, $beforeScope): SpecifiedTypes {
				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				$exprNode = $expr->expr;
				if ($expr->class instanceof Name) {
					if ($nameNarrowType === null) {
						throw new ShouldNotHappenException();
					}
					return $this->defaultNarrowingHelper->createSubjectTypes($s, $exprNode, $exprResult, $nameNarrowType, $context)->setRootExpr($expr);
				}

				// this branch is only reached when $expr->class is an Expr,
				// which is exactly when $classResult was set in processExpr
				if ($classResult === null) {
					throw new ShouldNotHappenException();
				}
				$classNameType = $classResult->getTypeOnScope($s, $nativeTypesPromoted);
				$result = $classNameType->toObjectTypeForInstanceofCheck();
				$type = $result->type;
				$uncertainty = $result->uncertainty;

				if (!$type->isSuperTypeOf(new MixedType())->yes()) {
					if ($context->true()) {
						$type = TypeCombinator::intersect(
							$type,
							new ObjectWithoutClassType(),
						);
						return $this->defaultNarrowingHelper->createSubjectTypes($s, $exprNode, $exprResult, $type, $context)->setRootExpr($expr);
					} elseif ($context->false() && !$uncertainty) {
						$exprType = $exprResult->getTypeOnScope($s, $nativeTypesPromoted);
						if (!$type->isSuperTypeOf($exprType)->yes()) {
							return $this->defaultNarrowingHelper->createSubjectTypes($s, $exprNode, $exprResult, $type, $context)->setRootExpr($expr);
						}
					}
				}
				if ($context->true()) {
					return $this->defaultNarrowingHelper->createSubjectTypes($s, $exprNode, $exprResult, new ObjectWithoutClassType(), $context)->setRootExpr($exprNode);
				}

				return (new SpecifiedTypes([], []))->setRootExpr($expr);
			},
		);
	}

}
