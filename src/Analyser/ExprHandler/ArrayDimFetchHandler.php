<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper;
use PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper;
use PHPStan\Analyser\IssetabilityDescriptor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableWriteOffset;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function is_string;

/**
 * @implements ExprHandler<ArrayDimFetch>
 */
#[AutowiredService]
final class ArrayDimFetchHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private MethodThrowPointHelper $methodThrowPointHelper,
		private MethodCallReturnTypeHelper $methodCallReturnTypeHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrayDimFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		// the receiver is read as a container - the offset read itself is
		// recorded below, once the dimension is known; both flow into the value
		if ($expr->dim === null) {
			$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow()->enterArrayDimFetchRoot());
			$this->markOffsetRead($nodeScopeResolver, $expr, null, $scope, $context);

			return $this->composeResult($nodeScopeResolver, $stmt, $expr, null, $varResult, $storage, $context, $beforeScope);
		}

		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $dimResult->getScope(), $storage, $nodeCallback, $context->enterDeepKeepingValueFlow()->enterArrayDimFetchRoot());
		$this->markOffsetRead($nodeScopeResolver, $expr, VariableWriteOffset::fromType($dimResult->getType()), $dimResult->getScope(), $context);

		return $this->composeResult($nodeScopeResolver, $stmt, $expr, $dimResult, $varResult, $storage, $context, $beforeScope);
	}

	/**
	 * Records the read of one offset of a local variable for the
	 * unused-variable check; an unset() target discards the offset's writes
	 * instead of reading them.
	 *
	 * @param int|string|null $offset
	 */
	private function markOffsetRead(NodeScopeResolver $nodeScopeResolver, ArrayDimFetch $expr, $offset, MutatingScope $scope, ExpressionContext $context): void
	{
		if ($context->isUnsetTarget()) {
			return;
		}
		if (!$expr->var instanceof Variable || !is_string($expr->var->name)) {
			return;
		}
		$nodeScopeResolver->markVariableOffsetRead($expr->var->name, $offset, $scope, $context->getValueFlowTarget());
	}

	/**
	 * Builds the offset read's ExpressionResult from the already-walked
	 * dimension and receiver results - the chain is not re-walked (only the
	 * ArrayAccess offsetGet simulation runs, over synthetic nodes).
	 * processExpr() routes through this; AssignHandler::prepareTarget() calls it
	 * to price a read-modify-write target from the write walk's child results.
	 */
	public function composeResult(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ArrayDimFetch $expr, ?ExpressionResult $dimResult, ExpressionResult $varResult, ExpressionResultStorage $storage, ExpressionContext $context, MutatingScope $beforeScope): ExpressionResult
	{
		$scope = $varResult->getScope();
		if ($expr->dim === null || $dimResult === null) {
			return $this->expressionResultFactory->create(
				$scope,
				beforeScope: $beforeScope,
				expr: $expr,
				hasYield: $varResult->hasYield(),
				isAlwaysTerminating: $varResult->isAlwaysTerminating(),
				throwPoints: $varResult->getThrowPoints(),
				impurePoints: $varResult->getImpurePoints(),
				containsNullsafe: $varResult->containsNullsafe(),
				// `$arr[]` only appears as an assignment target; reading it is a NeverType
				typeCallback: static fn (): Type => new NeverType(),
				specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
			);
		}

		$throwPoints = array_merge($dimResult->getThrowPoints(), $varResult->getThrowPoints());
		$impurePoints = array_merge($dimResult->getImpurePoints(), $varResult->getImpurePoints());

		$varType = $varResult->getType();
		$offsetGetCall = null;
		if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
			$throwPoints = array_merge($throwPoints, $this->methodThrowPointHelper->getThrowPointsForCallOnType(
				$scope,
				$context,
				$varType,
				new MethodCall(new TypeExpr($varType), 'offsetGet'),
			));
			// the offsetGet return type resolves directly in the typeCallback (per
			// flavour); the fabricated node is only the payload dynamic return
			// type extensions receive - nothing walks it. Gated by the same
			// maybe-ArrayAccess condition, so plain arrays never reach it.
			$offsetGetCall = new MethodCall($expr->var, new Identifier('offsetGet'), [new Arg($expr->dim)]);
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $dimResult->hasYield() || $varResult->hasYield(),
			isAlwaysTerminating: $dimResult->isAlwaysTerminating() || $varResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			containsNullsafe: $varResult->containsNullsafe(),
			issetabilityDescriptor: IssetabilityDescriptor::offset($varResult, $dimResult),
			typeCallback: function (bool $nativeTypesPromoted) use ($varResult, $dimResult, $offsetGetCall, $scope): Type {
				$offsetAccessibleType = ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType());
				$shortCircuit = static fn (Type $type): Type => $varResult->containsNullsafe() && TypeCombinator::containsNull($offsetAccessibleType)
					? TypeCombinator::addNull($type)
					: $type;

				if (
					$offsetGetCall !== null
					&& !$offsetAccessibleType->isArray()->yes()
					&& (new ObjectType(ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()
				) {
					if ($nativeTypesPromoted) {
						$methodReflection = $scope->getMethodReflection($offsetAccessibleType, 'offsetGet');
						if ($methodReflection === null) {
							return $shortCircuit(new ErrorType());
						}

						return $shortCircuit(ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants())->getNativeReturnType());
					}

					return $shortCircuit($this->methodCallReturnTypeHelper->methodCallReturnType($scope, $offsetAccessibleType, 'offsetGet', $offsetGetCall) ?? new ErrorType());
				}

				return $shortCircuit($offsetAccessibleType->getOffsetValueType(($nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType())));
			},
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypesWithNullsafeFan($expr, $context, $beforeScope, $nativeTypesPromoted),
		);
	}

}
