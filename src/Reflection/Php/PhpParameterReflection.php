<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use ReflectionParameter;
use Throwable;

class PhpParameterReflection implements ParameterReflectionWithPhpDocs
{

	private ReflectionParameter $reflection;

	private ?Type $phpDocType;

	private ?Type $type = null;

	private ?Type $nativeType = null;

	private ?string $declaringClassName;

	public function __construct(
		ReflectionParameter $reflection,
		?Type $phpDocType,
		?string $declaringClassName,
	)
	{
		$this->reflection = $reflection;
		$this->phpDocType = $phpDocType;
		$this->declaringClassName = $declaringClassName;
	}

	public function isOptional(): bool
	{
		return $this->reflection->isOptional();
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getType(): Type
	{
		if ($this->type === null) {
			$phpDocType = $this->phpDocType;
			if ($phpDocType !== null) {
				try {
					if ($this->reflection->isDefaultValueAvailable() && $this->reflection->getDefaultValue() === null) {
						$phpDocType = TypeCombinator::addNull($phpDocType);
					}
				} catch (Throwable $e) {
					// pass
				}
			}

			$this->type = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getType(),
				$phpDocType,
				$this->declaringClassName,
				$this->isVariadic(),
			);
		}

		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->reflection->isPassedByReference()
			? PassedByReference::createCreatesNewVariable()
			: PassedByReference::createNo();
	}

	public function isVariadic(): bool
	{
		return $this->reflection->isVariadic();
	}

	public function getPhpDocType(): Type
	{
		if ($this->phpDocType !== null) {
			return $this->phpDocType;
		}

		return new MixedType();
	}

	public function getNativeType(): Type
	{
		if ($this->nativeType === null) {
			$this->nativeType = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getType(),
				null,
				$this->declaringClassName,
				$this->isVariadic(),
			);
		}

		return $this->nativeType;
	}

	public function getDefaultValue(): ?Type
	{
		try {
			if ($this->reflection->isDefaultValueAvailable()) {
				$defaultValue = $this->reflection->getDefaultValue();
				return ConstantTypeHelper::getTypeFromValue($defaultValue);
			}
		} catch (Throwable $e) {
			return null;
		}

		return null;
	}

}
