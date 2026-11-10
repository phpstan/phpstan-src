<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * The magic Foo::class constant, so that restricted class constant usage extensions are called for it too.
 * PHP does not allow a real constant named "class", so extensions can recognise it by its name.
 */
final class ClassNameConstantReflection implements ClassConstantReflection
{

	public function __construct(private ClassReflection $classReflection)
	{
	}

	public function getValueExpr(): Expr
	{
		return new String_($this->classReflection->getName());
	}

	public function isFinal(): bool
	{
		return true;
	}

	public function isFinalByKeyword(): bool
	{
		return true;
	}

	public function hasPhpDocType(): bool
	{
		return false;
	}

	public function getPhpDocType(): ?Type
	{
		return null;
	}

	public function hasNativeType(): bool
	{
		return false;
	}

	public function getNativeType(): ?Type
	{
		return null;
	}

	public function getAttributes(): array
	{
		return [];
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->classReflection;
	}

	public function isStatic(): bool
	{
		return true;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getName(): string
	{
		return 'class';
	}

	public function describe(): string
	{
		return sprintf('%s::class', $this->classReflection->getDisplayName());
	}

	public function isBuiltin(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->classReflection->isBuiltin());
	}

	public function getValueType(): Type
	{
		return new ConstantStringType($this->classReflection->getName(), true);
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getFileName(): ?string
	{
		return $this->classReflection->getFileName();
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return null;
	}

}
