<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

class NativeParameterWithPhpDocsReflection implements ParameterReflectionWithPhpDocs
{

	private string $name;

	private bool $optional;

	private \PHPStan\Type\Type $type;

	private \PHPStan\Type\Type $phpDocType;

	private \PHPStan\Type\Type $nativeType;

	private \PHPStan\Reflection\PassedByReference $passedByReference;

	private bool $variadic;

	private ?\PHPStan\Type\Type $defaultValue;

	private bool $variadicParameterAlreadyExpanded;

	public function __construct(
		string $name,
		bool $optional,
		Type $type,
		Type $phpDocType,
		Type $nativeType,
		PassedByReference $passedByReference,
		bool $variadic,
		?Type $defaultValue,
		bool $variadicParameterAlreadyExpanded = false
	)
	{
		$this->name = $name;
		$this->optional = $optional;
		$this->type = $type;
		$this->phpDocType = $phpDocType;
		$this->nativeType = $nativeType;
		$this->passedByReference = $passedByReference;
		$this->variadic = $variadic;
		$this->defaultValue = $defaultValue;
		$this->variadicParameterAlreadyExpanded = $variadicParameterAlreadyExpanded;
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
		$type = $this->type;
		if ($this->variadic && !$this->variadicParameterAlreadyExpanded) {
			$type = new ArrayType(new IntegerType(), $type);
		}

		return $type;
	}

	public function getPhpDocType(): Type
	{
		return $this->phpDocType;
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

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['name'],
			$properties['optional'],
			$properties['type'],
			$properties['phpDocType'],
			$properties['nativeType'],
			$properties['passedByReference'],
			$properties['variadic'],
			$properties['defaultValue']
		);
	}

}
