<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
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
use PHPStan\Node\NullsafePropertyFetchExpressionNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<NullsafePropertyFetch>
 */
#[AutowiredService]
final class NullsafePropertyFetchHandler implements ExprHandler
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
		return $expr instanceof NullsafePropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		// the receiver is processed ONCE here, on the pre-ensure scope; the
		// plain-twin walk below CONSUMES its stored result instead of re-walking
		// it. Its result carries the receiver's real (possibly null) type - the
		// short-circuit decision needs to know it can be null, which reading the
		// ensured-non-null state would hide. An enclosing isset/empty/?? ensure
		// may have deviced the receiver in scope state, so the ensure stack's
		// original type still wins.
		$processedReceiverResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());
		$scope = $processedReceiverResult->getScope();
		$receiverType = $this->nonNullabilityHelper->getActiveEnsuredOriginalType($expr->var, false) ?? $processedReceiverResult->getType();
		$receiverNativeType = $this->nonNullabilityHelper->getActiveEnsuredOriginalType($expr->var, true) ?? $processedReceiverResult->getNativeType();
		// carry the receiver type to NullsafePropertyFetchRule so it reads it from
		// here instead of asking the scope for the unprocessed receiver.
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new NullsafePropertyFetchExpressionNode($expr, $receiverType, $receiverNativeType), $beforeScope, $storage, $context);
		$nonNullabilityResult = $this->nonNullabilityHelper->ensureShallowNonNullability($scope, $scope, $expr->var);
		// pre-store the receiver's ensured-position view: rules suspended at the
		// plain twin's callback ask about the receiver BEFORE the twin walk
		// consumes it, and must see the same (deviced non-null) answer the twin
		// walk itself will consume - exactly what storing the receiver walked
		// inside the twin used to produce
		$nodeScopeResolver->storeExpressionResult($storage, $expr->var, $processedReceiverResult->atAskPosition($nonNullabilityResult->getScope()));
		$attributes = array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch' => true]);
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		$propertyFetch = new PropertyFetch(
			$expr->var,
			$expr->name,
			$attributes,
		);
		$exprResult = $nodeScopeResolver->processExprNodeConsumingStored($stmt, $propertyFetch, $nonNullabilityResult->getScope(), $storage, $nodeCallback, $context);
		$scope = $this->nonNullabilityHelper->revertNonNullability($exprResult->getScope(), $nonNullabilityResult->getSpecifiedExpressions());

		// The `?->`'s own type on the asking scope. $receiverType is the receiver's
		// real type, captured before it was ensured non-null; reading its stored
		// result here would see the non-null device type and drop the
		// short-circuit's null.
		$nullsafeTypeCallback = static function (bool $nativeTypesPromoted) use ($exprResult, $receiverType): Type {
			if ($receiverType->isNull()->yes()) {
				return new NullType();
			}
			if (!TypeCombinator::containsNull($receiverType)) {
				return $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
			}

			// the plain fetch was already priced on the ensured (null-removed)
			// scope during processExpr - its result is the fetch's type on the
			// non-null receiver; the short-circuit contributes the null
			return TypeCombinator::union(
				$nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType(),
				new NullType(),
			);
		};

		// the receiver's stored result, for composing the receiver-not-null
		// narrowing without re-walking the chain
		$receiverResult = $processedReceiverResult;
		// lazily memoized receiver-is-null branch scope of the decomposition
		$leftFalseyScope = null;

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $exprResult->hasYield(),
			isAlwaysTerminating: false,
			throwPoints: $exprResult->getThrowPoints(),
			impurePoints: $exprResult->getImpurePoints(),
			containsNullsafe: true,
			typeCallback: $nullsafeTypeCallback,
			specifyTypesCallback: function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $propertyFetch, $exprResult, $receiverResult, $nonNullabilityResult, $beforeScope, $nodeScopeResolver, &$leftFalseyScope): SpecifiedTypes {
				if ($context->null()) {
					return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
				}

				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;

				// `$x?->...` narrows like ($x !== null) && $x->..., composed from
				// the captured receiver and plain-twin results - the fabricated
				// NotIdentical is only printed into holder keys, never walked
				$notIdenticalNode = new NotIdentical($expr->var, new ConstFetch(new Name('null')));
				$leftTypes = function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($expr, $receiverResult, $notIdenticalNode): SpecifiedTypes {
					if ($ctx->null()) {
						return $this->defaultNarrowingHelper->specifyDefaultTypes($notIdenticalNode, $ctx);
					}

					return $this->defaultNarrowingHelper->createSubjectTypes($scope, $expr->var, $receiverResult, new NullType(), $ctx->negate());
				};
				$rightTypes = static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes => $exprResult->getSpecifiedTypesForScope($scope, $ctx);

				$types = $this->booleanNarrowingHelper->specifyConjunction(
					$nodeScopeResolver,
					$s,
					$context,
					$expr,
					$notIdenticalNode,
					$leftTypes,
					static fn (): MutatingScope => $nonNullabilityResult->getScope(),
					// the plain twin was walked on the ensured-non-null scope - that
					// is the left-truthy evaluation point; the receiver-is-null
					// branch scope has no walk analog and derives on first demand
					static function () use ($beforeScope, $leftTypes, &$leftFalseyScope): MutatingScope {
						return $leftFalseyScope ??= $beforeScope->applySpecifiedTypes($leftTypes($beforeScope, TypeSpecifierContext::createFalsey()));
					},
					$propertyFetch,
					$rightTypes,
					static fn (): MutatingScope => $exprResult->getFalseyScope(),
				)->setRootExpr($expr);

				$nullSafeTypes = $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
				return $context->true() ? $types->unionWith($nullSafeTypes) : $types->intersectWith($nullSafeTypes);
			},
			// Inside-out copy of TypeSpecifier::createForExpr()'s `?->` handling.
			// The short-circuit's null surfaces here, never by walking the chain:
			// a receiver that is itself a ?-> composes through the parent handler.
			createTypesCallback: function (Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $propertyFetch, $exprResult, $receiverResult, $nullsafeTypeCallback, $beforeScope): SpecifiedTypes {
				// null() context: createForExpr never computes $containsNull and
				// emits no entry for the subject - behave the same.
				if ($context->null()) {
					return (new SpecifiedTypes())->setRootExpr($expr);
				}

				$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
				$nullsafeType = $nullsafeTypeCallback($nativeTypesPromoted);
				if ($context->true()) {
					$containsNull = !$type->isNull()->no() && !$nullsafeType->isNull()->no();
				} else {
					$containsNull = !TypeCombinator::containsNull($type) && !$nullsafeType->isNull()->no();
				}

				// The ?-> may legitimately be null (e.g. narrowed to a nullable
				// $type): keep the ?-> node's own key only, no plain chain, no
				// receiver-not-null - exactly createForExpr's containsNull branch.
				if ($containsNull) {
					return $this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $context)->setRootExpr($expr);
				}

				// !containsNull: the plain inner propertyFetch narrowed by $type
				// (createNullsafeTypes), the original ?-> key (createForExpr's
				// double-key), and "receiver is not null".
				// the receiver composes through its own result so a nullsafe
				// receiver fans "not null" down its whole chain
				return $this->defaultNarrowingHelper->createSubjectTypes($s, $propertyFetch, $exprResult, $type, $context)
					->unionWith($this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $context))
					->unionWith($this->defaultNarrowingHelper->createSubjectTypes($s, $expr->var, $receiverResult, new NullType(), TypeSpecifierContext::createFalse()))
					->setRootExpr($expr);
			},
		);
	}

}
