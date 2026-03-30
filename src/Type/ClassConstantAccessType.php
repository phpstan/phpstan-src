<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function sprintf;

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

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
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
		return sprintf('%s::%s', $this->classType->describe($level), $this->constantName);
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->classType)
			&& !($this->classType instanceof StaticType);
	}

	protected function getResult(): Type
	{
		if ($this->classType->hasConstant($this->constantName)->yes()) {
			return $this->classType->getConstant($this->constantName)->getValueType();
		}

		return new ErrorType();
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$classType = $cb($this->classType);

		if ($this->classType === $classType) {
			return $this;
		}

		return new self($classType, $this->constantName);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$classType = $cb($this->classType, $right->classType);

		if ($this->classType === $classType) {
			return $this;
		}

		return new self($classType, $this->constantName);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(
			new ConstFetchNode(
				$this->classType->describe(VerbosityLevel::typeOnly()),
				$this->constantName,
			),
		);
	}

}
