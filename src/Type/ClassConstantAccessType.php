<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;

final class ClassConstantAccessType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(
		private Type $classType,
		private string $constantName,
	)
	{
	}

	public function getReferencedClasses(): array
	{
		return $this->classType->getReferencedClasses();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->classType->getReferencedTemplateTypes($positionVariance);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->classType->equals($type->classType)
			&& $this->constantName === $type->constantName;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->resolve()->describe($level);
	}

	public function isResolvable(): bool
	{
		return !($this->classType instanceof StaticType);
	}

	protected function getResult(): Type
	{
		foreach ($this->classType->getObjectClassReflections() as $classReflection) {
			if (!$classReflection->hasConstant($this->constantName)) {
				continue;
			}

			return $classReflection->getConstant($this->constantName)->getValueType();
		}

		return new MixedType();
	}

	public function traverse(callable $cb): Type
	{
		$newClassType = $cb($this->classType);
		if ($newClassType !== $this->classType) {
			return new self($newClassType, $this->constantName);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$newClassType = $cb($this->classType, $right->classType);
		if ($newClassType !== $this->classType) {
			return new self($newClassType, $this->constantName);
		}

		return $this;
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(new ConstFetchNode('static', $this->constantName));
	}

}
