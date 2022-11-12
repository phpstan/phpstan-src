<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class NativeFunctionReflection implements FunctionReflection
{

	private Assertions $assertions;

	private TrinaryLogic $returnsByReference;

	/**
	 * @param ParametersAcceptorWithPhpDocs[] $variants
	 */
	public function __construct(
		private string $name,
		private array $variants,
		private ?Type $throwType,
		private TrinaryLogic $hasSideEffects,
		private bool $isDeprecated,
		?Assertions $assertions = null,
		private ?string $phpDocComment = null,
		?TrinaryLogic $returnsByReference = null,
	)
	{
		$this->assertions = $assertions ?? Assertions::createEmpty();
		$this->returnsByReference = $returnsByReference ?? TrinaryLogic::createMaybe();
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
	 * @return ParametersAcceptorWithPhpDocs[]
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
		foreach ($this->variants as $variant) {
			foreach ($variant->getParameters() as $parameter) {
				if ($parameter->passedByReference()->yes()) {
					return TrinaryLogic::createYes();
				}
			}
		}

		return $this->hasSideEffects;
	}

	private function isVoid(): bool
	{
		foreach ($this->variants as $variant) {
			if (!$variant->getReturnType()->isVoid()->yes()) {
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

	public function getDocComment(): ?string
	{
		return $this->phpDocComment;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->returnsByReference;
	}

}
