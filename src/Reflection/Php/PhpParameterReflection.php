<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;

class PhpParameterReflection implements ParameterReflectionWithPhpDocs
{

	private ?Type $type = null;

	private ?Type $nativeType = null;

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ReflectionParameter $reflection,
		private ?Type $phpDocType,
		private ?string $declaringClassName,
		private ?Type $outType,
	)
	{
	}

	public function isOptional(): bool
	{
		return $this->reflection->isOptional();
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getType(): Type
	{
		if ($this->type === null) {
			$phpDocType = $this->phpDocType;
			if (
				$phpDocType !== null
				&& $this->reflection->isDefaultValueAvailable()
			) {
				$defaultValueType = $this->initializerExprTypeResolver->getType(
					$this->reflection->getDefaultValueExpression(),
					InitializerExprContext::fromReflectionParameter($this->reflection),
				);
				if ($defaultValueType instanceof NullType) {
					$phpDocType = TypeCombinator::addNull($phpDocType);
				}
			}

			$this->type = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getType(),
				$phpDocType,
				$this->declaringClassName,
				$this->isVariadic(),
			);
		}

		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->reflection->isPassedByReference()
			? PassedByReference::createCreatesNewVariable()
			: PassedByReference::createNo();
	}

	public function isVariadic(): bool
	{
		return $this->reflection->isVariadic();
	}

	public function getPhpDocType(): Type
	{
		if ($this->phpDocType !== null) {
			return $this->phpDocType;
		}

		return new MixedType();
	}

	public function getNativeType(): Type
	{
		if ($this->nativeType === null) {
			$this->nativeType = TypehintHelper::decideTypeFromReflection(
				$this->reflection->getType(),
				null,
				$this->declaringClassName,
				$this->isVariadic(),
			);
		}

		return $this->nativeType;
	}

	public function getDefaultValue(): ?Type
	{
		if ($this->reflection->isDefaultValueAvailable()) {
			return $this->initializerExprTypeResolver->getType(
				$this->reflection->getDefaultValueExpression(),
				InitializerExprContext::fromReflectionParameter($this->reflection),
			);
		}

		return null;
	}

	public function getOutType(): ?Type
	{
		return $this->outType;
	}

}
