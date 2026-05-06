<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node\Expr;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RewrittenDeclaringClassClassConstantReflection implements ClassConstantReflection
{

	public function __construct(
		private ClassReflection $declaringClass,
		private ClassConstantReflection $constantReflection,
	)
	{
	}

	public function getValueExpr(): Expr
	{
		return $this->constantReflection->getValueExpr();
	}

	public function isFinal(): bool
	{
		return $this->constantReflection->isFinal();
	}

	public function isFinalByKeyword(): bool
	{
		return $this->constantReflection->isFinalByKeyword();
	}

	public function hasPhpDocType(): bool
	{
		return $this->constantReflection->hasPhpDocType();
	}

	public function getPhpDocType(): ?Type
	{
		return $this->constantReflection->getPhpDocType();
	}

	public function hasNativeType(): bool
	{
		return $this->constantReflection->hasNativeType();
	}

	public function getNativeType(): ?Type
	{
		return $this->constantReflection->getNativeType();
	}

	public function getAttributes(): array
	{
		return $this->constantReflection->getAttributes();
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->constantReflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->constantReflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->constantReflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->constantReflection->getDocComment();
	}

	public function getName(): string
	{
		return $this->constantReflection->getName();
	}

	public function getValueType(): Type
	{
		return $this->constantReflection->getValueType();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->constantReflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->constantReflection->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->constantReflection->isInternal();
	}

	public function getFileName(): ?string
	{
		return $this->constantReflection->getFileName();
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return $this->constantReflection->getResolvedPhpDoc();
	}

	public function getInitializerExprType(): Type
	{
		return $this->constantReflection->getInitializerExprType();
	}

}
