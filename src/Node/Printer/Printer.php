<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\CloneReinitializationExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Node\Expr\PossiblyImpureCallExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function preg_match;
use function sprintf;
use function str_contains;

/**
 * @api
 */
#[AutowiredService(as: Printer::class)]
final class Printer extends Standard
{

	/**
	 * Every construct the printer descends into comes back through here, and
	 * each level of a chain is printed again in turn for its own expression
	 * key, so a chain of depth N costs O(N^2). Remembering each expression's
	 * printed form on its node makes the levels below it O(1) — and it is the
	 * very same cache ExprPrinter fills, so a key printed once is never
	 * rebuilt, wherever it was first reached from.
	 *
	 * Only the standalone form is remembered: at a lower precedence the
	 * printer may parenthesise, and a form containing a newline was indented
	 * for the level it was printed at, while expression keys are printed at
	 * the top level.
	 */
	#[Override]
	protected function p(
		Node $node,
		int $precedence = self::MAX_PRECEDENCE,
		int $lhsPrecedence = self::MAX_PRECEDENCE,
		bool $parentFormatPreserved = false,
	): string
	{
		if (
			!$node instanceof Expr
			|| $precedence !== self::MAX_PRECEDENCE
			|| $lhsPrecedence !== self::MAX_PRECEDENCE
		) {
			return parent::p($node, $precedence, $lhsPrecedence, $parentFormatPreserved);
		}

		$printed = $node->getAttribute(ExprPrinter::ATTRIBUTE_CACHE_KEY);
		if ($printed !== null) {
			return $printed;
		}

		$printed = parent::p($node, parentFormatPreserved: $parentFormatPreserved);
		if (!str_contains($printed, "\n")) {
			$node->setAttribute(ExprPrinter::ATTRIBUTE_CACHE_KEY, $printed);
		}

		return $printed;
	}

	/**
	 * Normalize curly-brace member access with a constant string name to the
	 * bareword form, so that e.g. `$obj->{'n'}` and `$obj->n` (or `$obj->{'n'}()`
	 * and `$obj->n()`) produce identical expression keys and are treated as the
	 * same member by the analyser.
	 */
	#[Override]
	protected function pObjectProperty(Node $node): string
	{
		if (
			$node instanceof String_
			&& preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $node->value) === 1
		) {
			return $node->value;
		}

		return parent::pObjectProperty($node);
	}

	/**
	 * The `kind` attribute records how a node was spelled in the source, not
	 * what it means: `'k'`, `"k"` and a nowdoc holding `k` are the same string,
	 * `1` and `0x1` are the same integer, `array(...)` and `[...]` are the same
	 * array. Printing it back would make `$a['k']` and `$a["k"]` different
	 * expression keys, so the analyser would track them as two unrelated
	 * expressions and narrowing done on one would not apply to the other.
	 *
	 * Everything below therefore prints one canonical form per value, chosen
	 * without looking at `kind`.
	 */
	#[Override]
	protected function pScalar_String(String_ $node): string // phpcs:ignore
	{
		// keep escape sequences readable and the printed form single-line
		if (preg_match('/[\x00-\x1f\x7f]/', $node->value) === 1) {
			return '"' . $this->escapeString($node->value, '"') . '"';
		}

		return $this->pSingleQuotedString($node->value);
	}

	#[Override]
	protected function pScalar_InterpolatedString(InterpolatedString $node): string // phpcs:ignore
	{
		return '"' . $this->pEncapsList($node->parts, '"') . '"';
	}

	#[Override]
	protected function pScalar_Int(Int_ $node): string // phpcs:ignore
	{
		if ($node->getAttribute('kind', Int_::KIND_DEC) === Int_::KIND_DEC) {
			return parent::pScalar_Int($node);
		}

		return parent::pScalar_Int(new Int_($node->value));
	}

	#[Override]
	protected function pExpr_Array(Expr\Array_ $node): string // phpcs:ignore
	{
		if ($node->getAttribute('kind') === Expr\Array_::KIND_SHORT) {
			return parent::pExpr_Array($node);
		}

		return parent::pExpr_Array(new Expr\Array_($node->items, ['kind' => Expr\Array_::KIND_SHORT]));
	}

	#[Override]
	protected function pExpr_List(Expr\List_ $node): string // phpcs:ignore
	{
		if ($node->getAttribute('kind') === Expr\List_::KIND_ARRAY) {
			return parent::pExpr_List($node);
		}

		return parent::pExpr_List(new Expr\List_($node->items, ['kind' => Expr\List_::KIND_ARRAY]));
	}

	#[Override]
	protected function pExpr_Cast_Double(Cast\Double $node, int $precedence, int $lhsPrecedence): string // phpcs:ignore
	{
		return $this->pPrefixOp(Cast\Double::class, '(float) ', $node->expr, $precedence, $lhsPrecedence);
	}

	/**
	 * `true`, `false` and `null` are the only case-insensitive constant names
	 * in PHP, and `\true` means the same as `true`.
	 */
	#[Override]
	protected function pExpr_ConstFetch(Expr\ConstFetch $node): string // phpcs:ignore
	{
		$lowerName = $node->name->toLowerString();
		if (in_array($lowerName, ['true', 'false', 'null'], true)) {
			return $lowerName;
		}

		return parent::pExpr_ConstFetch($node);
	}

	protected function pPHPStan_Node_TypeExpr(TypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanType(%s)', $expr->getExprType()->describe(VerbosityLevel::precise()));
	}

	protected function pPHPStan_Node_NativeTypeExpr(NativeTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanNativeType(%s, %s)', $expr->getPhpDocType()->describe(VerbosityLevel::precise()), $expr->getNativeType()->describe(VerbosityLevel::precise()));
	}

	protected function pPHPStan_Node_UnsetOffsetExpr(UnsetOffsetExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanUnsetOffset(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
	}

	protected function pPHPStan_Node_ExistingArrayDimFetch(ExistingArrayDimFetch $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanExistingArrayDimFetch(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
	}

	protected function pPHPStan_Node_SetOffsetValueTypeExpr(SetOffsetValueTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanSetOffsetValueType(%s, %s, %s)', $this->p($expr->getVar()), $expr->getDim() !== null ? $this->p($expr->getDim()) : 'null', $this->p($expr->getValue()));
	}

	protected function pPHPStan_Node_SetExistingOffsetValueTypeExpr(SetExistingOffsetValueTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanSetExistingOffsetValueType(%s, %s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()), $this->p($expr->getValue()));
	}

	protected function pPHPStan_Node_AlwaysRememberedExpr(AlwaysRememberedExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanRemembered(%s)', $this->p($expr->getExpr()));
	}

	protected function pPHPStan_Node_PossiblyImpureCallExpr(PossiblyImpureCallExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanPossiblyImpure(%s, %s)', $this->p($expr->callExpr), $this->p($expr->impactedExpr));
	}

	protected function pPHPStan_Node_PropertyInitializationExpr(PropertyInitializationExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanPropertyInitialization(%s)', $expr->getPropertyName());
	}

	protected function pPHPStan_Node_CloneReinitializationExpr(CloneReinitializationExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanCloneReinitialization(%s)', $expr->getPropertyName());
	}

	protected function pPHPStan_Node_ForeachValueByRefExpr(ForeachValueByRefExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanForeachValueByRef(%s)', $this->p($expr->getExpr()));
	}

	protected function pPHPStan_Node_ParameterVariableOriginalValueExpr(ParameterVariableOriginalValueExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanParameterVariableOriginalValue(%s)', $expr->getVariableName());
	}

	protected function pPHPStan_Node_OriginalForeachKeyExpr(OriginalForeachKeyExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanOriginalForeachKey(%s)', $expr->getVariableName());
	}

	protected function pPHPStan_Node_OriginalForeachValueExpr(OriginalForeachValueExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanOriginalForeachValue(%s)', $expr->getVariableName());
	}

	protected function pPHPStan_Node_IntertwinedVariableByReferenceWithExpr(IntertwinedVariableByReferenceWithExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanIntertwinedVariableByReference(%s, %s, %s)', $expr->getVariableName(), $this->p($expr->getExpr()), $this->p($expr->getAssignedExpr()));
	}

	protected function pPHPStan_Node_IssetExpr(IssetExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanIssetExpr(%s)', $this->p($expr->getExpr()));
	}

	protected function pPHPStan_Node_BooleanOrNode(BooleanOrNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanBooleanOr(%s, %s)', $this->p($expr->getOriginalNode()->left), $this->p($expr->getOriginalNode()->right));
	}

	protected function pPHPStan_Node_BooleanAndNode(BooleanAndNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanBooleanAnd(%s, %s)', $this->p($expr->getOriginalNode()->left), $this->p($expr->getOriginalNode()->right));
	}

	protected function pPHPStan_Node_FunctionCallableNode(FunctionCallableNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanFunctionCallable(%s)', $this->p($expr->getOriginalNode()));
	}

	protected function pPHPStan_Node_MethodCallableNode(MethodCallableNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanMethodCallable(%s)', $this->p($expr->getOriginalNode()));
	}

	protected function pPHPStan_Node_StaticMethodCallableNode(StaticMethodCallableNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanStaticMethodCallable(%s)', $this->p($expr->getOriginalNode()));
	}

	protected function pPHPStan_Node_InstantiationCallableNode(InstantiationCallableNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanInstantiationCallable(%s)', $this->p($expr->getOriginalNode()));
	}

}
