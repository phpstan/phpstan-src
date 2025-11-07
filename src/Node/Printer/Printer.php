<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\OriginalForeachKeyExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Node\IssetExpr;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @api
 */
#[AutowiredService(as: Printer::class)]
final class Printer extends Standard
{

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

	protected function pPHPStan_Node_PropertyInitializationExpr(PropertyInitializationExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanPropertyInitialization(%s)', $expr->getPropertyName());
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

	protected function pPHPStan_Node_IntertwinedVariableByReferenceWithExpr(IntertwinedVariableByReferenceWithExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanIntertwinedVariableByReference(%s, %s, %s)', $expr->getVariableName(), $this->p($expr->getExpr()), $this->p($expr->getAssignedExpr()));
	}

	protected function pPHPStan_Node_IssetExpr(IssetExpr $expr): string // phpcs:ignore
	{
		return sprintf('__phpstanIssetExpr(%s)', $this->p($expr->getExpr()));
	}

}
