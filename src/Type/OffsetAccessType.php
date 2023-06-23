<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;

/** @api */
final class OffsetAccessType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(
		private Type $type,
		private Type $offset,
	)
	{
	}

	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->type->getReferencedClasses(),
			$this->offset->getReferencedClasses(),
		);
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
		return array_merge(
			$this->type->getReferencedTemplateTypes($positionVariance),
			$this->offset->getReferencedTemplateTypes($positionVariance),
		);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->type->equals($type->type)
			&& $this->offset->equals($type->offset);
	}

	public function describe(VerbosityLevel $level): string
	{
		$printer = new Printer();

		return $printer->print($this->toPhpDocNode());
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->type)
			&& !TypeUtils::containsTemplateType($this->offset);
	}

	protected function getResult(): Type
	{
		return $this->type->getOffsetValueType($this->offset);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$type = $cb($this->type);
		$offset = $cb($this->offset);

		if ($this->type === $type && $this->offset === $offset) {
			return $this;
		}

		return new self($type, $offset);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$type = $cb($this->type, $right->type);
		$offset = $cb($this->offset, $right->offset);

		if ($this->type === $type && $this->offset === $offset) {
			return $this;
		}

		return new self($type, $offset);
	}

	public function traverseWithVariance(TemplateTypeVariance $variance, callable $cb): Type
	{
		$type = $cb($this->type, $variance);
		$offset = $cb($this->offset, $variance);

		if ($this->type === $type && $this->offset === $offset) {
			return $this;
		}

		return new self($type, $offset);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new OffsetAccessTypeNode(
			$this->type->toPhpDocNode(),
			$this->offset->toPhpDocNode(),
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['type'],
			$properties['offset'],
		);
	}

}
