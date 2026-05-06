<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Type\Type;

/**
 * Reflection for a class constant.
 *
 * Combines ClassMemberReflection (declaring class, visibility) with
 * ConstantReflection (name, value type, deprecation) and adds class-constant-specific
 * features: the value expression AST, final modifier, and separate PHPDoc/native types.
 *
 * PHP 8.3+ supports native type declarations on class constants, so this interface
 * provides both PHPDoc and native type accessors (similar to property reflection).
 *
 * This is the return type of Type::getConstant() and Scope::getConstantReflection().
 *
 * @api
 * @api-do-not-implement
 */
interface ClassConstantReflection extends ClassMemberReflection, ConstantReflection
{

	public function getValueExpr(): Expr;

	public function isFinal(): bool;

	public function isFinalByKeyword(): bool;

	public function hasPhpDocType(): bool;

	public function getPhpDocType(): ?Type;

	public function hasNativeType(): bool;

	public function getNativeType(): ?Type;

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock;

	public function getInitializerExprType(): Type;

}
