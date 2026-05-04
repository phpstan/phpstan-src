<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RewrittenDeclaringClassMethodReflection implements ExtendedMethodReflection
{

	public function __construct(
		private ClassReflection $declaringClass,
		private ExtendedMethodReflection $methodReflection,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->methodReflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->methodReflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->methodReflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->methodReflection->getDocComment();
	}

	public function getVariants(): array
	{
		return $this->methodReflection->getVariants();
	}

	public function getOnlyVariant(): ExtendedParametersAcceptor
	{
		return $this->methodReflection->getOnlyVariant();
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return $this->methodReflection->getNamedArgumentsVariants();
	}

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return $this->methodReflection->acceptsNamedArguments();
	}

	public function getAsserts(): Assertions
	{
		return $this->methodReflection->getAsserts();
	}

	public function getSelfOutType(): ?Type
	{
		return $this->methodReflection->getSelfOutType();
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->methodReflection->returnsByReference();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->methodReflection->isFinalByKeyword();
	}

	public function isAbstract(): TrinaryLogic|bool
	{
		return $this->methodReflection->isAbstract();
	}

	public function isBuiltin(): TrinaryLogic|bool
	{
		return $this->methodReflection->isBuiltin();
	}

	public function isPure(): TrinaryLogic
	{
		return $this->methodReflection->isPure();
	}

	public function getAttributes(): array
	{
		return $this->methodReflection->getAttributes();
	}

	public function getName(): string
	{
		return $this->methodReflection->getName();
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this->methodReflection->getPrototype();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->methodReflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->methodReflection->getDeprecatedDescription();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->methodReflection->isFinal();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->methodReflection->isInternal();
	}

	public function getThrowType(): ?Type
	{
		return $this->methodReflection->getThrowType();
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->methodReflection->hasSideEffects();
	}

	public function mustUseReturnValue(): TrinaryLogic
	{
		return $this->methodReflection->mustUseReturnValue();
	}

}
