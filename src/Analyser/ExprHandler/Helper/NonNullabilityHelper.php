<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\EnsuredNonNullabilityResult;
use PHPStan\Analyser\EnsuredNonNullabilityResultExpression;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;
use function array_pop;
use function count;
use function get_class;
use function is_string;

#[AutowiredService]
final class NonNullabilityHelper implements PerFileAnalysisResettable
{

	/**
	 * The ensures currently in effect during the walk, innermost last. An
	 * ensure writes non-null "device" types into the scope so nested fetches
	 * walk without spurious possibly-null noise - indistinguishable from
	 * genuine narrowing in scope state. Handlers whose semantics depend on an
	 * expression's REAL nullability (a nullsafe operator's short-circuit)
	 * consult this stack for the pre-device type instead.
	 *
	 * @var list<array<string, array{Type, Type}>>
	 */
	private array $activeEnsures = [];

	/**
	 * Chain links an ensure could not device ahead of the walk - the scope
	 * cannot price them from its state (isPricedFromState()) - keyed by
	 * printed expression, one frame per active ensure. applyPendingEnsure()
	 * devices such a link when its walk completes, from the type the walk
	 * produced.
	 *
	 * @var list<array{keys: array<string, true>, classes: array<class-string<Expr>, true>}>
	 */
	private array $pendingEnsures = [];

	/**
	 * The devices applied at walk completion, per frame - reverted together
	 * with the frame's ahead-of-walk devices.
	 *
	 * @var list<list<EnsuredNonNullabilityResultExpression>>
	 */
	private array $lateEnsures = [];

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	/**
	 * An internal error escaping between an ensure and its revert would leave
	 * stale frames matched by common print keys ($this->foo) for the rest of
	 * the worker's batch - the per-file reset clears them.
	 */
	public function resetFileAnalysisState(): void
	{
		$this->activeEnsures = [];
		$this->pendingEnsures = [];
		$this->lateEnsures = [];
	}

	/**
	 * The pre-device type an active ensure saved for this expression, or null
	 * when no ensure covers it.
	 */
	public function getActiveEnsuredOriginalType(Expr $expr, bool $native): ?Type
	{
		if ($this->activeEnsures === []) {
			return null;
		}

		$key = $this->exprPrinter->printExpr($expr);
		for ($i = count($this->activeEnsures) - 1; $i >= 0; $i--) {
			if (isset($this->activeEnsures[$i][$key])) {
				return $this->activeEnsures[$i][$key][$native ? 1 : 0];
			}
		}

		return null;
	}

	public function ensureShallowNonNullability(MutatingScope $scope, MutatingScope $originalScope, Expr $exprToSpecify): EnsuredNonNullabilityResult
	{
		$result = $this->doEnsureShallowNonNullability($scope, $originalScope, $exprToSpecify);
		$this->pushActiveEnsure($result);

		return $result;
	}

	/**
	 * @param array{keys: array<string, true>, classes: array<class-string<Expr>, true>} $pending
	 */
	private function pushActiveEnsure(EnsuredNonNullabilityResult $result, array $pending = ['keys' => [], 'classes' => []]): void
	{
		$originals = [];
		foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
			$originals[$this->exprPrinter->printExpr($specifiedExpression->getExpression())] = [
				$specifiedExpression->getOriginalType(),
				$specifiedExpression->getOriginalNativeType(),
			];
		}
		$this->activeEnsures[] = $originals;
		$this->pendingEnsures[] = $pending;
		$this->lateEnsures[] = [];
	}

	/**
	 * Whether the scope prices the link from its state alone - a variable
	 * read, a fetch spine over such reads, a constant - so an ensure can
	 * device it ahead of the walk. Any other link (a call, a ternary, ...)
	 * has no type before its walk and is deviced when the walk completes:
	 * the state answer for an argument-less call would be its declared
	 * return type, which lacks what the walk resolves.
	 */
	private function isPricedFromState(Expr $expr, MutatingScope $scope): bool
	{
		if ($expr instanceof Variable) {
			return is_string($expr->name);
		}
		if ($scope->hasExpressionType($expr)->yes()) {
			return true;
		}
		if ($expr instanceof ArrayDimFetch) {
			return $expr->dim !== null
				&& $this->isPricedFromState($expr->var, $scope)
				&& $this->isPricedFromState($expr->dim, $scope);
		}
		if ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
			return $expr->name instanceof Node\Identifier && $this->isPricedFromState($expr->var, $scope);
		}
		if ($expr instanceof StaticPropertyFetch) {
			return $expr->name instanceof Node\VarLikeIdentifier
				&& ($expr->class instanceof Node\Name || $this->isPricedFromState($expr->class, $scope));
		}
		return $expr instanceof Node\Scalar\String_
			|| $expr instanceof Node\Scalar\Int_
			|| $expr instanceof Node\Scalar\Float_
			|| $expr instanceof Expr\ConstFetch
			|| ($expr instanceof Expr\ClassConstFetch && $expr->class instanceof Node\Name && $expr->name instanceof Node\Identifier);
	}

	/**
	 * Devices a chain link ensureNonNullability() left pending, now that its
	 * walk produced a type: the link's scopes track it as non-null, so its
	 * result answers what a link walked on an ensured-ahead scope answered.
	 * Any other node passes through untouched.
	 */
	public function applyPendingEnsure(Expr $expr, ExpressionResult $result): ExpressionResult
	{
		if ($this->pendingEnsures === []) {
			return $result;
		}

		$key = null;
		for ($i = count($this->pendingEnsures) - 1; $i >= 0; $i--) {
			// the node class screens the common case before the node is printed
			if (!isset($this->pendingEnsures[$i]['classes'][get_class($expr)])) {
				continue;
			}
			$key ??= $this->exprPrinter->printExpr($expr);
			if (!isset($this->pendingEnsures[$i]['keys'][$key])) {
				continue;
			}

			unset($this->pendingEnsures[$i]['keys'][$key]);
			$scope = $result->getScope();
			if ($scope->hasExpressionType($expr)->yes()) {
				// an earlier link printing the same way installed the device
				return $result;
			}

			$type = $result->getType();
			if ($type->isNull()->yes()) {
				return $result;
			}
			$typeWithoutNull = TypeCombinator::removeNull($type);
			if ($type->equals($typeWithoutNull)) {
				return $result;
			}

			$nativeType = $result->getNativeType();
			$nativeTypeWithoutNull = TypeCombinator::removeNull($nativeType);
			$this->activeEnsures[$i][$key] = [$type, $nativeType];
			$this->lateEnsures[$i][] = new EnsuredNonNullabilityResultExpression($expr, $type, $nativeType, TrinaryLogic::createYes());

			return $result->onNonNullabilityDevicedScopes(
				$result->getBeforeScope()->specifyExpressionType($expr, $typeWithoutNull, $nativeTypeWithoutNull, TrinaryLogic::createYes()),
				$scope->specifyExpressionType($expr, $typeWithoutNull, $nativeTypeWithoutNull, TrinaryLogic::createYes()),
			);
		}

		return $result;
	}

	private function doEnsureShallowNonNullability(MutatingScope $scope, MutatingScope $originalScope, Expr $exprToSpecify): EnsuredNonNullabilityResult
	{
		// the expression has not been processed into the storage yet (this runs
		// before processExprNode) - derive its current type from the scope's
		// tracked state instead of pricing the node on demand.
		$exprType = $scope->getStateType($exprToSpecify);
		$isNull = $exprType->isNull();
		if ($isNull->yes()) {
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$hasExpressionType = $originalScope->hasExpressionType($exprToSpecify);

		$exprTypeWithoutNull = TypeCombinator::removeNull($exprType);
		if ($exprType->equals($exprTypeWithoutNull)) {
			$originalExprType = $originalScope->getStateType($exprToSpecify);
			if (!$originalExprType->equals($exprTypeWithoutNull)) {
				$originalNativeType = $originalScope->doNotTreatPhpDocTypesAsCertain()->getStateType($exprToSpecify);

				return new EnsuredNonNullabilityResult($scope, [
					new EnsuredNonNullabilityResultExpression($exprToSpecify, $originalExprType, $originalNativeType, $hasExpressionType),
				]);
			}
			return new EnsuredNonNullabilityResult($scope, []);
		}

		$specifiedExpressions = [];

		// When narrowing an ArrayDimFetch, specifyExpressionType also recursively
		// narrows the parent array's offset type via intersection with HasOffsetValueType.
		// To properly revert this, we must also save and restore the parent expression's type.
		if ($exprToSpecify instanceof Expr\ArrayDimFetch && $exprToSpecify->dim !== null) {
			$parentExpr = $exprToSpecify->var;
			$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression(
				$parentExpr,
				$scope->getStateType($parentExpr),
				$scope->doNotTreatPhpDocTypesAsCertain()->getStateType($parentExpr),
				$originalScope->hasExpressionType($parentExpr),
			);
		}

		// Keep the "might not be defined" certainty of variables so that rules
		// reporting possibly undefined variables still see it. For any other
		// expression a Maybe certainty would make the narrowed type invisible to
		// Scope::getType(), throwing the narrowing away.
		$certainty = TrinaryLogic::createYes();
		if ($hasExpressionType->maybe() && $exprToSpecify instanceof Variable) {
			$certainty = $hasExpressionType;
		}

		$nativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getStateType($exprToSpecify);
		$specifiedExpressions[] = new EnsuredNonNullabilityResultExpression($exprToSpecify, $exprType, $nativeType, $certainty);
		$scope = $scope->specifyExpressionType(
			$exprToSpecify,
			$exprTypeWithoutNull,
			TypeCombinator::removeNull($nativeType),
			$certainty,
		);

		return new EnsuredNonNullabilityResult(
			$scope,
			$specifiedExpressions,
		);
	}

	public function ensureNonNullability(MutatingScope $scope, Expr $expr): EnsuredNonNullabilityResult
	{
		$specifiedExpressions = [];
		$pending = ['keys' => [], 'classes' => []];
		$originalScope = $scope;
		$scope = $this->lookForExpressionCallback($scope, $expr, function ($scope, $expr) use (&$specifiedExpressions, &$pending, $originalScope) {
			// a link the scope cannot price from its state has no type before
			// its walk: device it when the walk completes instead of pricing
			// the node ahead of its turn
			if (!$this->isPricedFromState($expr, $scope)) {
				$pending['keys'][$this->exprPrinter->printExpr($expr)] = true;
				$pending['classes'][get_class($expr)] = true;

				return $scope;
			}

			$result = $this->doEnsureShallowNonNullability($scope, $originalScope, $expr);
			foreach ($result->getSpecifiedExpressions() as $specifiedExpression) {
				$specifiedExpressions[] = $specifiedExpression;
			}
			return $result->getScope();
		}, false);

		$result = new EnsuredNonNullabilityResult($scope, $specifiedExpressions);
		$this->pushActiveEnsure($result, $pending);

		return $result;
	}

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	public function revertNonNullability(MutatingScope $scope, array $specifiedExpressions): MutatingScope
	{
		array_pop($this->activeEnsures);
		array_pop($this->pendingEnsures);
		$lateEnsures = array_pop($this->lateEnsures) ?? [];
		foreach (array_merge($specifiedExpressions, $lateEnsures) as $specifiedExpressionResult) {
			if ($specifiedExpressionResult->getCertainty()->no()) {
				$scope = $scope->invalidateExpression($specifiedExpressionResult->getExpression());
				continue;
			}
			$scope = $scope->specifyExpressionType(
				$specifiedExpressionResult->getExpression(),
				$specifiedExpressionResult->getOriginalType(),
				$specifiedExpressionResult->getOriginalNativeType(),
				$specifiedExpressionResult->getCertainty(),
			);
		}

		return $scope;
	}

	/**
	 * @param Closure(MutatingScope, Expr): MutatingScope $callback
	 */
	private function lookForExpressionCallback(MutatingScope $scope, Expr $expr, Closure $callback, bool $includeExpr = true): MutatingScope
	{
		// $includeExpr is false only for the outermost operand: ensuring its chain
		// links non-null lets it be walked without spurious "possibly null" noise,
		// but the operand's own value must keep its real (nullable) type - that is
		// the type the isset/empty/?? verdict and narrowing read from its result.
		// a receiver the scope can never track and that is never null (a `new`,
		// an array/closure literal, a scalar) has nothing to ensure - and reading
		// its state would have to walk a node not processed yet
		if (
			$includeExpr
			&& (!$expr instanceof ArrayDimFetch || $expr->dim !== null)
			&& !$expr instanceof Expr\New_
			&& !$expr instanceof Expr\Array_
			&& !$expr instanceof Expr\Closure
			&& !$expr instanceof Expr\ArrowFunction
			&& !$expr instanceof Node\Scalar
		) {
			$scope = $callback($scope, $expr);
		}

		if ($expr instanceof ArrayDimFetch) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
			$scope = $this->lookForExpressionCallback($scope, $expr->var, $callback);
		} elseif ($expr instanceof StaticPropertyFetch && $expr->class instanceof Expr) {
			$scope = $this->lookForExpressionCallback($scope, $expr->class, $callback);
		} elseif ($expr instanceof List_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				$scope = $this->lookForExpressionCallback($scope, $item->value, $callback);
			}
		}

		return $scope;
	}

}
