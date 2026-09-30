<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClassConstantAccessType.cpp')]
final class ClassConstantAccessType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param Type|null $nativeType The native type next to the PHPDoc type this is - see withNativeType().
	 */
	public function __construct(
		private Type $type,
		private string $constantName,
		private ?Type $nativeType = null,
	)
	{
	}

	/**
	 * The PHPDoc type of a parameter or of a return type with its native type. Which constants
	 * static:: or T:: stands for is known only once the class is, so the two are combined only
	 * then - see TypehintHelper::decideType().
	 */
	public function withNativeType(Type $nativeType): self
	{
		return new self($this->type, $this->constantName, $nativeType);
	}

	public function getReferencedClasses(): array
	{
		return $this->type->getReferencedClasses();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->type->getReferencedTemplateTypes($positionVariance);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self || $this->constantName !== $type->constantName || !$this->type->equals($type->type)) {
			return false;
		}

		if ($this->nativeType === null || $type->nativeType === null) {
			return $this->nativeType === $type->nativeType;
		}

		return $this->nativeType->equals($type->nativeType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->resolve()->describe($level);
	}

	/**
	 * Not while the class is a template type or static - both can still turn out to be a class
	 * declaring constants the class known now does not.
	 */
	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type) && !$this->type instanceof StaticType;
	}

	protected function getResult(): Type
	{
		return ClassConstantPatternResolver::resolve($this->type, $this->constantName, $this->nativeType);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$type = $cb($this->type);

		if ($this->type === $type) {
			return $this;
		}

		return new self($type, $this->constantName, $this->nativeType);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$type = $cb($this->type, $right->type);

		if ($this->type === $type) {
			return $this;
		}

		return new self($type, $this->constantName, $this->nativeType);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(new ConstFetchNode($this->type instanceof TemplateType ? $this->type->getName() : 'static', $this->constantName));
	}

}
