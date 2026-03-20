<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RuntimeConstantReflection implements ConstantReflection
{

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private string $name,
		private Type $valueType,
		private ?string $fileName,
		private TrinaryLogic $isDeprecated,
		private ?string $deprecatedDescription,
		private array $attributes,
		private bool $internal,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function describe(): string
	{
		return $this->name;
	}

	public function isBuiltin(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->internal);
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function getFileName(): ?string
	{
		return $this->fileName;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->isDeprecated;
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->deprecatedDescription;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

}
