<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;

class PhpParameterFromParserNodeReflection implements ParameterReflectionWithPhpDocs
{

	private string $name;

	private bool $optional;

	private Type $realType;

	private ?Type $phpDocType;

	private PassedByReference $passedByReference;

	private ?Type $defaultValue;

	private bool $variadic;

	private ?Type $type = null;

	public function __construct(
		string $name,
		bool $optional,
		Type $realType,
		?Type $phpDocType,
		PassedByReference $passedByReference,
		?Type $defaultValue,
		bool $variadic,
	)
	{
		$this->name = $name;
		$this->optional = $optional;
		$this->realType = $realType;
		$this->phpDocType = $phpDocType;
		$this->passedByReference = $passedByReference;
		$this->defaultValue = $defaultValue;
		$this->variadic = $variadic;
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
		if ($this->type === null) {
			$phpDocType = $this->phpDocType;
			if ($phpDocType !== null && $this->defaultValue !== null) {
				if ($this->defaultValue instanceof NullType) {
					$inferred = $phpDocType->inferTemplateTypes($this->defaultValue);
					if ($inferred->isEmpty()) {
						$phpDocType = TypeCombinator::addNull($phpDocType);
					}
				}
			}
			$this->type = TypehintHelper::decideType($this->realType, $phpDocType);
		}

		return $this->type;
	}

	public function getPhpDocType(): Type
	{
		return $this->phpDocType ?? new MixedType();
	}

	public function getNativeType(): Type
	{
		return $this->realType;
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

}
