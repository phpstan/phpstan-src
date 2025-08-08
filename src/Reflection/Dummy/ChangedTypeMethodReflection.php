<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function count;
use function is_bool;

final class ChangedTypeMethodReflection implements ExtendedMethodReflection
{

	/**
	 * @param list<ExtendedParametersAcceptor> $variants
	 * @param list<ExtendedParametersAcceptor>|null $namedArgumentsVariants
	 */
	public function __construct(
		private ClassReflection $declaringClass,
		private ExtendedMethodReflection $reflection,
		private array $variants,
		private ?array $namedArgumentsVariants,
		private ?Type $selfOutType,
		private ?Type $throwType,
		private Assertions $assertions,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this->reflection->getPrototype();
	}

	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getOnlyVariant(): ExtendedParametersAcceptor
	{
		$variants = $this->getVariants();
		if (count($variants) !== 1) {
			throw new ShouldNotHappenException();
		}

		return $variants[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return $this->namedArgumentsVariants;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->reflection->getDeprecatedDescription();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->reflection->isFinal();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->reflection->isFinalByKeyword();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->reflection->isInternal();
	}

	public function isBuiltin(): TrinaryLogic
	{
		$builtin = $this->reflection->isBuiltin();
		if (is_bool($builtin)) {
			return TrinaryLogic::createFromBoolean($builtin);
		}

		return $builtin;
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->reflection->hasSideEffects();
	}

	public function getAsserts(): Assertions
	{
		return $this->assertions;
	}

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return $this->reflection->acceptsNamedArguments();
	}

	public function getSelfOutType(): ?Type
	{
		return $this->selfOutType;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->reflection->returnsByReference();
	}

	public function isAbstract(): TrinaryLogic
	{
		$abstract = $this->reflection->isAbstract();
		if (is_bool($abstract)) {
			return TrinaryLogic::createFromBoolean($abstract);
		}

		return $abstract;
	}

	public function isPure(): TrinaryLogic
	{
		return $this->reflection->isPure();
	}

	public function getAttributes(): array
	{
		return $this->reflection->getAttributes();
	}

}
