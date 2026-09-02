<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\EmptyExpressionNode;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<Empty_>
 */
#[AutowiredService]
final class EmptyHandler implements ExprHandler
{

	public function __construct(
		private NonNullabilityHelper $nonNullabilityHelper,
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private BooleanNarrowingHelper $booleanNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Empty_;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureNonNullability($scope, $expr->expr);
		$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($nonNullabilityResult->getScope(), $expr->expr);
		$exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $exprResult->getScope();
		$scope = $this->nonNullabilityHelper->revertNonNullability($scope, $nonNullabilityResult->getSpecifiedExpressions());
		$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $expr->expr);

		$chainResults = [];
		$this->defaultNarrowingHelper->captureChainResults($expr->expr, $storage, $chainResults);

		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new EmptyExpressionNode($expr, $exprResult), $beforeScope, $storage, $context);

		// lazily memoized branch scopes of the !isset($x) || !$x decomposition
		/** @var array{MutatingScope, MutatingScope, MutatingScope}|null $foldScopes */
		$foldScopes = null;

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: $exprResult->isAlwaysTerminating(),
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			typeCallback: static function (bool $nativeTypesPromoted) use ($exprResult, $beforeScope): Type {
				$result = $exprResult->getIssetabilityResolution($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope, false)->notEmpty();
				if ($result === null) {
					return new BooleanType();
				}

				return new ConstantBooleanType(!$result);
			},
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $exprResult, $chainResults, $nodeScopeResolver, $beforeScope, &$foldScopes): SpecifiedTypes {
				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				$isset = $exprResult->getIssetabilityResolution($s, false)->isSet(static fn (): bool => true);
				if ($isset === false) {
					return new SpecifiedTypes();
				}

				// empty($x) narrows like !isset($x) || !$x, composed through the
				// disjunction helper - the fabricated nodes are only printed
				// into holder keys, never walked
				$issetNode = new Expr\Isset_([$expr->expr]);
				$notIssetNode = new Expr\BooleanNot($issetNode);
				$notExprNode = new Expr\BooleanNot($expr->expr);

				$leftTypes = function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($chainResults, $expr, $exprResult, $issetNode, $notIssetNode): SpecifiedTypes {
					if ($ctx->null()) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($notIssetNode, $ctx);
					}
					$negated = $ctx->negate();
					$readType = $this->defaultNarrowingHelper->buildChainTypeReader($chainResults, $scope);
					if (!$negated->true()) {
						return $this->defaultNarrowingHelper->createIssetSingleSubjectNonTrueTypes($scope, $expr->expr, $exprResult, $readType, $negated, $issetNode);
					}

					return $this->defaultNarrowingHelper->createIssetTruthyChainTypes($scope, $expr->expr, $readType, $issetNode, $negated);
				};
				$leftType = static function (bool $nativeTypesPromoted) use ($exprResult, $beforeScope): Type {
					$issetabilityScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
					$result = $exprResult->getIssetabilityResolution($issetabilityScope, false)->isSet(static function (Type $type): ?bool {
						$isNull = $type->isNull();
						if ($isNull->maybe()) {
							return null;
						}

						return !$isNull->yes();
					});
					if ($result === null) {
						return new BooleanType();
					}

					return new ConstantBooleanType(!$result);
				};
				$rightTypes = function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($exprResult, $notExprNode): SpecifiedTypes {
					if ($ctx->null()) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($notExprNode, $ctx);
					}

					return $exprResult->getSpecifiedTypesForScope($scope, $ctx->negate());
				};
				$rightType = static function (bool $nativeTypesPromoted) use ($exprResult): Type {
					$bool = ($nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType())->toBoolean();
					if ($bool->isTrue()->yes()) {
						return new ConstantBooleanType(false);
					}
					if ($bool->isFalse()->yes()) {
						return new ConstantBooleanType(true);
					}

					return new BooleanType();
				};

				// the disjuncts' branch scopes derive from the evaluation point,
				// not the asking scope - computed once, reused across asks
				if ($foldScopes === null) {
					$leftTruthyScope = $beforeScope->applySpecifiedTypes($leftTypes($beforeScope, TypeSpecifierContext::createTruthy()));
					$leftFalseyScope = $beforeScope->applySpecifiedTypes($leftTypes($beforeScope, TypeSpecifierContext::createFalsey()));
					$foldScopes = [
						$leftTruthyScope,
						$leftFalseyScope,
						$leftFalseyScope->applySpecifiedTypes($rightTypes($leftFalseyScope, TypeSpecifierContext::createTruthy())),
					];
				}
				[$leftTruthyScope, $leftFalseyScope, $rightTruthyScope] = $foldScopes;

				return $this->booleanNarrowingHelper->specifyDisjunction(
					$nodeScopeResolver,
					$s,
					$context,
					$expr,
					$notIssetNode,
					$leftTypes,
					$leftType,
					static fn (): MutatingScope => $leftTruthyScope,
					static fn (): MutatingScope => $leftFalseyScope,
					$notExprNode,
					$rightTypes,
					$rightType,
					static fn (): MutatingScope => $rightTruthyScope,
				)->setRootExpr($expr);
			},
		);
	}

}
