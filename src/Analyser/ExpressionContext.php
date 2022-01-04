<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;

class ExpressionContext
{

	private function __construct(
		private bool $isDeep,
		private ?string $inAssignRightSideVariableName,
		private ?Type $inAssignRightSideType,
	)
	{
	}

	public static function createTopLevel(): self
	{
		return new self(false, null, null);
	}

	public static function createDeep(): self
	{
		return new self(true, null, null);
	}

	public function enterDeep(): self
	{
		if ($this->isDeep) {
			return $this;
		}

		return new self(true, $this->inAssignRightSideVariableName, $this->inAssignRightSideType);
	}

	public function isDeep(): bool
	{
		return $this->isDeep;
	}

	public function enterRightSideAssign(string $variableName, Type $type): self
	{
		return new self($this->isDeep, $variableName, $type);
	}

	public function getInAssignRightSideVariableName(): ?string
	{
		return $this->inAssignRightSideVariableName;
	}

	public function getInAssignRightSideType(): ?Type
	{
		return $this->inAssignRightSideType;
	}

}
