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
		private Type $type,
		private string $constantName,
	)
	{
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
		return $type instanceof self
			&& $this->type->equals($type->type)
			&& $this->constantName === $type->constantName;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->resolve()->describe($level);
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type);
	}

	protected function getResult(): Type
	{
		$classReflections = $this->type->getObjectClassReflections();
		if (count($classReflections) !== 1) {
			if (!$this->type->hasConstant($this->constantName)->yes()) {
				return new ErrorType();
			}

			return $this->type->getConstant($this->constantName)->getValueType();
		}

		$constantClassReflection = $classReflections[0];
		if (!$constantClassReflection->hasConstant($this->constantName)) {
			return new ErrorType();
		}

		if ($constantClassReflection->isEnum() && $constantClassReflection->hasEnumCase($this->constantName)) {
			return new Enum\EnumCaseObjectType($constantClassReflection->getName(), $this->constantName);
		}

		$constantReflection = $constantClassReflection->getConstant($this->constantName);

		if (
			!$constantClassReflection->isFinal()
			&& !$constantReflection->isFinal()
			&& !$constantReflection->hasPhpDocType()
			&& !$constantReflection->hasNativeType()
		) {
			return new MixedType();
		}

		return $constantReflection->getValueType();
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

		return new self($type, $this->constantName);
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

		return new self($type, $this->constantName);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(new ConstFetchNode('static', $this->constantName));
	}

}
