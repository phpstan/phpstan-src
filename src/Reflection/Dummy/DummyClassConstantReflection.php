<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PhpParser\Node\Expr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use stdClass;
use function sprintf;

final class DummyClassConstantReflection implements ClassConstantReflection
{

	public function __construct(private string $name)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		return $reflectionProvider->getClass(stdClass::class);
	}

	public function isFinal(): bool
	{
		return false;
	}

	public function isFinalByKeyword(): bool
	{
		return false;
	}

	public function getFileName(): ?string
	{
		return null;
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

	public function getName(): string
	{
		return $this->name;
	}

	public function describe(): string
	{
		return sprintf('%s::%s', $this->getDeclaringClass()->getDisplayName(), $this->name);
	}

	public function isBuiltin(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->getDeclaringClass()->isBuiltin());
	}

	public function getValueType(): Type
	{
		return new MixedType();
	}

	public function getValueExpr(): Expr
	{
		return new TypeExpr(new MixedType());
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDocComment(): ?string
	{
		return null;
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

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return null;
	}

}
