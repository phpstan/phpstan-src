<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use Override;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\CloneReinitializationExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalForeachValueExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
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
use PHPStan\Node\NullsafeFirstClassCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Type\VerbosityLevel;
use function preg_match;
use function sprintf;

/**
 * @api
 */
#[AutowiredService(as: Printer::class)]
final class Printer extends Standard
{

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

	protected function pPHPStan_Node_TypeExpr(TypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanType(%s)', $expr->getExprType()->describe(VerbosityLevel::precise()));
	}

	protected function pPHPStan_Node_NativeTypeExpr(NativeTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanNativeType(%s, %s)', $expr->getPhpDocType()->describe(VerbosityLevel::precise()), $expr->getNativeType()->describe(VerbosityLevel::precise()));
	}

	protected function pPHPStan_Node_GetOffsetValueTypeExpr(GetOffsetValueTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanGetOffsetValueType(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
	}

	protected function pPHPStan_Node_UnsetOffsetExpr(UnsetOffsetExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanUnsetOffset(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
	}

	protected function pPHPStan_Node_GetIterableValueTypeExpr(GetIterableValueTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanGetIterableValueType(%s)', $this->p($expr->getExpr()));
	}

	protected function pPHPStan_Node_GetIterableKeyTypeExpr(GetIterableKeyTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanGetIterableKeyType(%s)', $this->p($expr->getExpr()));
	}

	protected function pPHPStan_Node_ExistingArrayDimFetch(ExistingArrayDimFetch $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanExistingArrayDimFetch(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
	}

	protected function pPHPStan_Node_OriginalPropertyTypeExpr(OriginalPropertyTypeExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanOriginalPropertyType(%s)', $this->p($expr->getPropertyFetch()));
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

	protected function pPHPStan_Node_NullsafeFirstClassCallableNode(NullsafeFirstClassCallableNode $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanNullsafeFirstClassCallable(%s)', $this->p($expr->getOriginalNode()));
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
