<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class RuntimeConstantReflection implements GlobalConstantReflection
{

	private string $name;

	private Type $valueType;

	private ?string $fileName;

	public function __construct(
		string $name,
		Type $valueType,
		?string $fileName,
	)
	{
		$this->name = $name;
		$this->valueType = $valueType;
		$this->fileName = $fileName;
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
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
