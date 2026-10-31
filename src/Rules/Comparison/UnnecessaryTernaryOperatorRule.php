<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\TypeCombinator;
use function count;
use function is_float;
use function is_string;
use function sprintf;

/**
 * Reports `$a ?: X`, `$cond ? $a : X` and `$cond ? X : $a` when the result is
 * always `$a`: whenever the ternary picks `X`, `$a` already has its value.
 *
 * @implements Rule<Node\Expr\Ternary>
 */
#[RegisteredRule(level: 4)]
final class UnnecessaryTernaryOperatorRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		private ReflectionProvider $reflectionProvider,
		private ExprPrinter $exprPrinter,
		#[AutowiredParameter(ref: '%featureToggles.unnecessaryTernary%')]
		private bool $unnecessaryTernary,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Ternary::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$this->unnecessaryTernary) {
			return [];
		}

		$error = $this->check($node, $scope);
		if ($error === null) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
			return [];
		}

		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, true, $error);
			return [];
		}

		return [$error];
	}

	private function check(Node\Expr\Ternary $node, Scope $scope): ?IdentifierRuleError
	{
		// TernaryOperatorConstantConditionRule already reports these
		$conditionType = $this->helper->getBooleanType($scope, $node->cond);
		if (count($conditionType->getConstantScalarValues()) === 1) {
			return null;
		}

		$result = $this->findResult($node, $scope, !$this->treatPhpDocTypesAsCertain);
		if ($result === null) {
			return null;
		}

		$ruleErrorBuilder = RuleErrorBuilder::message(sprintf(
			$node->if === null
				? 'Short ternary operator ?: is unnecessary because it always results in %s.'
				: 'Ternary operator is unnecessary because it always results in %s.',
			$this->exprPrinter->printExpr($result),
		))->identifier('ternary.unnecessary');

		if (
			$this->treatPhpDocTypesAsCertain
			&& $this->treatPhpDocTypesAsCertainTip
			&& $this->findResult($node, $scope, true) === null
		) {
			$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		}

		return $ruleErrorBuilder->build();
	}

	/**
	 * The branch the ternary always results in, or null.
	 */
	private function findResult(Node\Expr\Ternary $node, Scope $scope, bool $native): ?Expr
	{
		// Without the ternary, the result is evaluated every time, and the
		// condition and the other branch are not evaluated at all.
		if (!$this->isPure($node->else, $scope)) {
			return null;
		}

		if (!$this->isTruthinessNarrowedExactly($node->cond, $scope, $native)) {
			return null;
		}

		if ($node->if === null) {
			if (!$this->isAlwaysResult($scope->filterByFalseyValue($node->cond), $node->cond, $scope, $node->else, $native)) {
				return null;
			}

			return $node->cond;
		}

		if (!$this->isPure($node->cond, $scope) || !$this->isPure($node->if, $scope)) {
			return null;
		}

		// The result then also runs in the scope of the other branch, where a
		// function call can get an argument it does not accept, for example
		// `$s === false ? 0 : strlen($s)`.
		if (!$this->containsCall($node->if) && $this->isAlwaysResult($scope->filterByFalseyValue($node->cond), $node->if, $scope, $node->else, $native)) {
			return $node->if;
		}
		if (!$this->containsCall($node->else) && $this->isAlwaysResult($scope->filterByTruthyValue($node->cond), $node->else, $scope, $node->if, $native)) {
			return $node->else;
		}

		return null;
	}

	/**
	 * Whether $result always has the single value of $other in $branchScope,
	 * the scope in which the ternary picks $other.
	 */
	private function isAlwaysResult(Scope $branchScope, Expr $result, Scope $scope, Expr $other, bool $native): bool
	{
		// a fresh copy, because the walked node keeps the type it had in its own branch
		$result = (new NodeTraverser(new CloningVisitor()))->traverse([$result])[0];
		if (!$result instanceof Expr) {
			return false;
		}

		if ($native) {
			$resultType = $branchScope->getNativeType($result);
			$otherType = $scope->getNativeType($other);
		} else {
			$resultType = $branchScope->getType($result);
			$otherType = $scope->getType($other);
		}

		// enum cases are not constant values, but each of them is a single value
		if (!$otherType->isConstantValue()->yes() && count($otherType->getFiniteTypes()) !== 1) {
			return false;
		}
		if (!$resultType->equals($otherType)) {
			return false;
		}

		// 0.0 === -0.0, so `$f ?: 0.0` turns -0.0 into 0.0
		foreach ($otherType->getConstantScalarValues() as $value) {
			if (is_float($value)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Narrowing by truthiness treats every object as truthy, but an empty
	 * SimpleXMLElement or a zero GMP is falsy. Then the scope of the other
	 * branch does not hold all values that reach it.
	 */
	private function isTruthinessNarrowedExactly(Expr $cond, Scope $scope, bool $native): bool
	{
		if ($cond instanceof Expr\BooleanNot) {
			return $this->isTruthinessNarrowedExactly($cond->expr, $scope, $native);
		}

		if (
			$cond instanceof Expr\BinaryOp\BooleanAnd
			|| $cond instanceof Expr\BinaryOp\BooleanOr
			|| $cond instanceof Expr\BinaryOp\LogicalAnd
			|| $cond instanceof Expr\BinaryOp\LogicalOr
		) {
			return $this->isTruthinessNarrowedExactly($cond->left, $scope, $native) && $this->isTruthinessNarrowedExactly($cond->right, $scope, $native);
		}

		$type = $native ? $scope->getNativeType($cond) : $scope->getType($cond);
		if ($type->isObject()->no()) {
			return true;
		}

		return TypeCombinator::intersect($type, new ObjectWithoutClassType())->toBoolean()->isTrue()->yes();
	}

	private function containsCall(Expr $expr): bool
	{
		return (new NodeFinder())->findFirstInstanceOf([$expr], Expr\FuncCall::class) !== null;
	}

	/**
	 * Whether evaluating the expression can only produce its value, so that
	 * dropping it or evaluating it once more does not change anything.
	 */
	private function isPure(Expr $expr, Scope $scope): bool
	{
		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name);
		}

		if ($expr instanceof Node\Scalar\Int_ || $expr instanceof Node\Scalar\Float_ || $expr instanceof Node\Scalar\String_) {
			return true;
		}

		if ($expr instanceof Expr\ConstFetch) {
			return true;
		}

		if ($expr instanceof Expr\ClassConstFetch) {
			return $expr->class instanceof Node\Name && $expr->name instanceof Node\Identifier;
		}

		if ($expr instanceof Expr\Array_) {
			foreach ($expr->items as $item) {
				if ($item->unpack || $item->byRef) {
					return false;
				}
				if ($item->key !== null && !$this->isPure($item->key, $scope)) {
					return false;
				}
				if (!$this->isPure($item->value, $scope)) {
					return false;
				}
			}

			return true;
		}

		if (
			$expr instanceof Expr\BinaryOp\Identical
			|| $expr instanceof Expr\BinaryOp\NotIdentical
			|| $expr instanceof Expr\BinaryOp\BooleanAnd
			|| $expr instanceof Expr\BinaryOp\BooleanOr
			|| $expr instanceof Expr\BinaryOp\LogicalAnd
			|| $expr instanceof Expr\BinaryOp\LogicalOr
		) {
			return $this->isPure($expr->left, $scope) && $this->isPure($expr->right, $scope);
		}

		if ($expr instanceof Expr\BooleanNot) {
			return $this->isPure($expr->expr, $scope);
		}

		if ($expr instanceof Expr\Instanceof_) {
			return $expr->class instanceof Node\Name && $this->isPure($expr->expr, $scope);
		}

		if ($expr instanceof Expr\FuncCall) {
			if (!$expr->name instanceof Node\Name || $expr->isFirstClassCallable()) {
				return false;
			}
			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				return false;
			}
			if (!$this->reflectionProvider->getFunction($expr->name, $scope)->hasSideEffects()->no()) {
				return false;
			}
			foreach ($expr->getArgs() as $arg) {
				if ($arg->unpack || $arg->byRef || !$this->isPure($arg->value, $scope)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

}
