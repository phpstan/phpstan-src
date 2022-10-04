<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

class ResolvedMethodReflection implements ExtendedMethodReflection
{

	/** @var ParametersAcceptor[]|null */
	private ?array $variants = null;

	private ?Assertions $asserts = null;

	private Type|false|null $selfOutType = false;

	public function __construct(private ExtendedMethodReflection $reflection, private TemplateTypeMap $resolvedTemplateTypeMap)
	{
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this->reflection->getPrototype();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		$variants = $this->variants;
		if ($variants !== null) {
			return $variants;
		}

		$variants = [];
		foreach ($this->reflection->getVariants() as $variant) {
			$variants[] = new ResolvedFunctionVariant(
				$variant,
				$this->resolvedTemplateTypeMap,
				[],
			);
		}

		$this->variants = $variants;

		return $variants;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->reflection->getDeclaringClass();
	}

	public function getDeclaringTrait(): ?ClassReflection
	{
		if ($this->reflection instanceof PhpMethodReflection) {
			return $this->reflection->getDeclaringTrait();
		}

		return null;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->reflection->getDeprecatedDescription();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->reflection->isFinal();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->reflection->isInternal();
	}

	public function getThrowType(): ?Type
	{
		return $this->reflection->getThrowType();
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->reflection->hasSideEffects();
	}

	public function getAsserts(): Assertions
	{
		return $this->asserts ??= $this->reflection->getAsserts()->mapTypes(fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type, $this->resolvedTemplateTypeMap));
	}

	public function getSelfOutType(): ?Type
	{
		if ($this->selfOutType === false) {
			$selfOutType = $this->reflection->getSelfOutType();
			if ($selfOutType !== null) {
				$selfOutType = TemplateTypeHelper::resolveTemplateTypes($selfOutType, $this->resolvedTemplateTypeMap);
			}

			$this->selfOutType = $selfOutType;
		}

		return $this->selfOutType;
	}

}
