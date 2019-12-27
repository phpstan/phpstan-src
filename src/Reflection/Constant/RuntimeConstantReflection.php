<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class RuntimeConstantReflection implements GlobalConstantReflection
{

	/** @var string */
	private $name;

	/** @var Type */
	private $valueType;

	public function __construct(string $name, Type $valueType)
	{
		$this->name = $name;
		$this->valueType = $valueType;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
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
