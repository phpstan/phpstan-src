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
 */
interface ClassConstantReflection extends ClassMemberReflection, ConstantReflection
{

	/**
	 * Returns the AST expression for this constant's value.
	 *
	 * This is the raw expression from the parser, useful for rules that
	 * need to inspect the constant's definition.
	 */
	public function getValueExpr(): Expr;

	/** Whether this constant is declared final (PHP 8.1+). */
	public function isFinal(): bool;

	/** Whether this constant has a PHPDoc @var type. */
	public function hasPhpDocType(): bool;

	/**
	 * Returns the PHPDoc @var type for this constant, or null if none.
	 */
	public function getPhpDocType(): ?Type;

	/** Whether this constant has a native PHP type declaration (PHP 8.3+). */
	public function hasNativeType(): bool;

	/**
	 * Returns the native PHP type declaration, or null if none.
	 */
	public function getNativeType(): ?Type;

	/**
	 * Returns the resolved PHPDoc block for this constant, or null if none exists.
	 */
	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock;

}
