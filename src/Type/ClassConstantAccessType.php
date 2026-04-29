<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function count;

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
			&& $this->constantName === $type->constantName
			&& $this->type->equals($type->type);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->resolve()->describe($level);
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($this->type->hasConstant($this->constantName)->yes()) {
			$valueType = $this->type->getConstant($this->constantName)->getValueType();
			return $otherType->isSuperTypeOf($valueType);
		}

		return $otherType->isSuperTypeOf($this->resolve());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		if ($this->type->hasConstant($this->constantName)->yes()) {
			$valueType = $this->type->getConstant($this->constantName)->getValueType();
			return $acceptingType->accepts($valueType, $strictTypes);
		}

		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isAcceptedBy($acceptingType, $strictTypes);
		}

		return $acceptingType->accepts($result, $strictTypes);
	}

	protected function getResult(): Type
	{
		if (!$this->type->hasConstant($this->constantName)->yes()) {
			return new ErrorType();
		}

		$constantReflection = $this->type->getConstant($this->constantName);

		$classReflections = $this->type->getObjectClassReflections();
		$isFinalClass = count($classReflections) === 1 && $classReflections[0]->isFinal();

		if ($isFinalClass || $constantReflection->isFinal()) {
			return $constantReflection->getValueType();
		}

		if (!$constantReflection->hasPhpDocType() && !$constantReflection->hasNativeType()) {
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
		return new ConstTypeNode(new ConstFetchNode((string) $this->type->toPhpDocNode(), $this->constantName));
	}

}
