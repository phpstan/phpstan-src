<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

class NativeFunctionReflection implements \PHPStan\Reflection\FunctionReflection
{

	private string $name;

	/** @var \PHPStan\Reflection\ParametersAcceptor[] */
	private array $variants;

	private ?\PHPStan\Type\Type $throwType;

	private TrinaryLogic $hasSideEffects;

	private bool $isDeprecated;

	/**
	 * @param string $name
	 * @param \PHPStan\Reflection\ParametersAcceptor[] $variants
	 * @param \PHPStan\Type\Type|null $throwType
	 * @param \PHPStan\TrinaryLogic $hasSideEffects
	 */
	public function __construct(
		string $name,
		array $variants,
		?Type $throwType,
		TrinaryLogic $hasSideEffects,
		bool $isDeprecated
	)
	{
		$this->name = $name;
		$this->variants = $variants;
		$this->throwType = $throwType;
		$this->hasSideEffects = $hasSideEffects;
		$this->isDeprecated = $isDeprecated;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
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

}
