<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RuntimeConstantReflection implements GlobalConstantReflection
{

	public function __construct(
		private string $name,
		private Type $valueType,
		private ?string $fileName,
		private TrinaryLogic $isDeprecated,
		private ?string $deprecatedDescription,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
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

}
