<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function count;

final class NativeFunctionReflection implements FunctionReflection
{

	private Assertions $assertions;

	private TrinaryLogic $returnsByReference;

	/**
	 * @param ParametersAcceptorWithPhpDocs[] $variants
	 * @param ParametersAcceptorWithPhpDocs[]|null $namedArgumentsVariants
	 */
	public function __construct(
		private string $name,
		private array $variants,
		private ?array $namedArgumentsVariants,
		private ?Type $throwType,
		private TrinaryLogic $hasSideEffects,
		private bool $isDeprecated,
		?Assertions $assertions,
		private ?string $phpDocComment,
		?TrinaryLogic $returnsByReference,
		private bool $acceptsNamedArguments,
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

	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs
	{
		$variants = $this->getVariants();
		if (count($variants) !== 1) {
			throw new ShouldNotHappenException();
		}

		return $variants[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return $this->namedArgumentsVariants;
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

	public function isPure(): TrinaryLogic
	{
		if ($this->hasSideEffects()->yes()) {
			return TrinaryLogic::createNo();
		}

		return $this->hasSideEffects->negate();
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

	public function acceptsNamedArguments(): bool
	{
		return $this->acceptsNamedArguments;
	}

}
