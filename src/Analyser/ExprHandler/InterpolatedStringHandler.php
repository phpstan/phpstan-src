<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_merge;
use function spl_object_id;

/**
 * @implements ExprHandler<InterpolatedString>
 */
#[AutowiredService]
final class InterpolatedStringHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ImplicitToStringCallHelper $implicitToStringCallHelper,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof InterpolatedString;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		$partResults = [];
		foreach ($expr->parts as $part) {
			if (!$part instanceof Expr) {
				continue;
			}
			$partResult = $nodeScopeResolver->processExprNode($stmt, $part, $scope, $storage, $nodeCallback, $context->enterDeep());
			$partResults[spl_object_id($part)] = $partResult;
			$hasYield = $hasYield || $partResult->hasYield();
			$throwPoints = array_merge($throwPoints, $partResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $partResult->getImpurePoints());

			$toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($part, $partResult->getType(), $scope);
			$throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());

			$isAlwaysTerminating = $isAlwaysTerminating || $partResult->isAlwaysTerminating();
			$scope = $partResult->getScope();
		}

		// each part type was captured at its own evaluation point in the sequence
		$typeCallback = function (Expr $e, MutatingScope $s) use ($partResults): Type {
			if (!$e instanceof InterpolatedString) {
				throw new ShouldNotHappenException();
			}

			$resultType = new ConstantStringType('');
			$first = true;
			foreach ($e->parts as $part) {
				if ($part instanceof InterpolatedStringPart) {
					$partType = new ConstantStringType($part->value);
				} else {
					$partResult = $partResults[spl_object_id($part)] ?? null;
					$partType = ($partResult !== null ? $partResult->getTypeForScope($s) : $s->getType($part))->toString();
				}
				if ($first) {
					$resultType = $partType;
					$first = false;
					continue;
				}

				$resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
			}

			return $resultType;
		};

		return new ExpressionResult(
			$scope,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$resultType = null;
		foreach ($expr->parts as $part) {
			if ($part instanceof InterpolatedStringPart) {
				$partType = new ConstantStringType($part->value);
			} else {
				$partType = $scope->getType($part)->toString();
			}
			if ($resultType === null) {
				$resultType = $partType;
				continue;
			}

			$resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
		}

		return $resultType ?? new ConstantStringType('');
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
