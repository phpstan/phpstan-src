<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

class NativeFunctionReflection implements FunctionReflection
{

	/**
	 * @param ParametersAcceptor[] $variants
	 */
	public function __construct(
		private string $name,
		private array $variants,
		private ?Type $throwType,
		private TrinaryLogic $hasSideEffects,
		private bool $isDeprecated,
		private ?Assertions $assertions = null,
	)
	{
		$this->assertions ??= Assertions::createEmpty();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getFileName(): ?string
	{
		return null;
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isDeprecated);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasSideEffects(): TrinaryLogic
	{
		if ($this->isVoid()) {
			return TrinaryLogic::createYes();
		}

		return $this->hasSideEffects;
	}

	private function isVoid(): bool
	{
		foreach ($this->variants as $variant) {
			if (!$variant->getReturnType() instanceof VoidType) {
				return false;
			}
		}

		return true;
	}

	public function isBuiltin(): bool
	{
		return true;
	}

	public function getAsserts(): Assertions
	{
		return $this->assertions;
	}

}
