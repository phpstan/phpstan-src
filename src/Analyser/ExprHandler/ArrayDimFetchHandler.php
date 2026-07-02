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
use PHPStan\Analyser\ExprHandler\Helper\IllegalOffsetTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use TypeError;
use function array_merge;

/**
 * @implements ExprHandler<ArrayDimFetch>
 */
#[AutowiredService]
final class ArrayDimFetchHandler implements ExprHandler
{

	public function __construct(private PhpVersion $phpVersion)
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
				truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
				falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			);
		}

		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeep());
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $dimResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		$throwPoints = array_merge($dimResult->getThrowPoints(), $varResult->getThrowPoints());
		$impurePoints = array_merge($dimResult->getImpurePoints(), $varResult->getImpurePoints());
		$scope = $varResult->getScope();

		$varType = $scope->getType($expr->var);
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

		if (
			$this->phpVersion->throwsTypeErrorForIllegalOffsets()
			// only array and string offset reads throw TypeError for illegal offsets,
			// reads on null and other scalars emit a warning, ArrayAccess objects go through offsetGet()
			&& (!$varType->isArray()->no() || !$varType->isString()->no())
			&& IllegalOffsetTypeHelper::mayOffsetThrowTypeError($scope->getType($expr->dim))
		) {
			$throwPoints[] = InternalThrowPoint::createExplicit($scope, new ObjectType(TypeError::class), $expr, false);
		}

		return new ExpressionResult(
			$scope,
			hasYield: $dimResult->hasYield() || $varResult->hasYield(),
			isAlwaysTerminating: $dimResult->isAlwaysTerminating() || $varResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
