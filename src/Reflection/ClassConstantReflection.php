<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/** @api */
interface ClassConstantReflection extends ClassMemberReflection, ConstantReflection
{

	public function getValueExpr(): Expr;

	public function isFinal(): bool;

	public function hasPhpDocType(): bool;

	public function getPhpDocType(): ?Type;

	public function hasNativeType(): bool;

	public function getNativeType(): ?Type;

}
