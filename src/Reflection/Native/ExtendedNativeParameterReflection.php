<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

final class ExtendedNativeParameterReflection implements ExtendedParameterReflection
{

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private string $name,
		private bool $optional,
		private Type $type,
		private Type $phpDocType,
		private Type $nativeType,
		private PassedByReference $passedByReference,
		private bool $variadic,
		private ?Type $defaultValue,
		private ?Type $outType,
		private TrinaryLogic $immediatelyInvokedCallable,
		private ?Type $closureThisType,
		private array $attributes,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getPhpDocType(): Type
	{
		return $this->phpDocType;
	}

	public function hasNativeType(): bool
	{
		return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
	}

	public function getNativeType(): Type
	{
		return $this->nativeType;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

	public function getOutType(): ?Type
	{
		return $this->outType;
	}

	public function isImmediatelyInvokedCallable(): TrinaryLogic
	{
		return $this->immediatelyInvokedCallable;
	}

	public function getClosureThisType(): ?Type
	{
		return $this->closureThisType;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function union(ExtendedParameterReflection $other): self
	{
		return new self(
			$this->name,
			$this->optional && $other->isOptional(),
			TypeCombinator::union($this->type, $other->getType()),
			TypeCombinator::union($this->phpDocType, $other->getPhpDocType()),
			TypeCombinator::union($this->nativeType, $other->getNativeType()),
			$this->passedByReference->combine($other->passedByReference()),
			$this->variadic && $other->isVariadic(),
			$this->optional && $other->isOptional() ? $this->defaultValue : null,
			$this->outType !== null && $other->getOutType() !== null ? TypeCombinator::union($this->outType, $other->getOutType()) : null,
			$this->immediatelyInvokedCallable->and($other->isImmediatelyInvokedCallable()),
			$this->closureThisType !== null && $other->getClosureThisType() !== null ? TypeCombinator::union($this->closureThisType, $other->getClosureThisType()) : null,
			array_merge($this->attributes, $other->getAttributes()),
		);
	}

}
