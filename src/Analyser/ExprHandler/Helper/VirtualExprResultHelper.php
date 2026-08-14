<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Type\Type;

/**
 * Builds ExpressionResults for virtual nodes whose answer is available without
 * a walk - the types they carry, or the already-processed results they compose.
 * The virtual node handlers delegate here, so a fabricated result and a walked
 * one are identical by construction.
 */
#[AutowiredService]
final class VirtualExprResultHelper
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function createTypeExprResult(MutatingScope $scope, TypeExpr|NativeTypeExpr $expr): ExpressionResult
	{
		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: $expr instanceof TypeExpr
				? static fn (bool $nativeTypesPromoted): Type => $expr->getExprType()
				: static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $expr->getNativeType() : $expr->getPhpDocType(),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

	public function createUnsetOffsetExprResult(MutatingScope $scope, UnsetOffsetExpr $expr, ExpressionResult $varResult, ExpressionResult $dimResult): ExpressionResult
	{
		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
			typeCallback: static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType())->unsetOffset($nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType()),
			specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
		);
	}

}
