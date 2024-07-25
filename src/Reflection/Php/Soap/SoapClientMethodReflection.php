<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php\Soap;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class SoapClientMethodReflection implements MethodReflection
{

	public function __construct(private ClassReflection $declaringClass, private string $name)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return false;
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
		return $this->name;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function getVariants(): array
	{
		return [
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				[],
				true,
				new MixedType(true),
			),
		];
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): Type
	{
		return new ObjectType('SoapFault');
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

}
