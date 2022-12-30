<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use function strtolower;

class AnnotationMethodReflection implements ExtendedMethodReflection
{

	/** @var FunctionVariant[]|null */
	private ?array $variants = null;

	/**
	 * @param AnnotationsMethodParameterReflection[] $parameters
	 */
	public function __construct(
		private string $name,
		private ClassReflection $declaringClass,
		private Type $returnType,
		private array $parameters,
		private bool $isStatic,
		private bool $isVariadic,
		private ?Type $throwType,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		if ($this->variants === null) {
			$this->variants = [
				new FunctionVariant(
					TemplateTypeMap::createEmpty(),
					null,
					$this->parameters,
					$this->isVariadic,
					$this->returnType,
				),
			];
		}
		return $this->variants;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		if (
			strtolower($this->getName()) !== '__construct'
			&& $this->returnType->isVoid()->yes()
		) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getAsserts(): Assertions
	{
		return Assertions::createEmpty();
	}

	public function getSelfOutType(): ?Type
	{
		return null;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

}
