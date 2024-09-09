<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/** @api */
class GenericIntegerRangeType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(private ?Type $min, private ?Type $max)
	{
	}

	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->min !== null ? $this->min->getReferencedClasses() : [],
			$this->max !== null ? $this->max->getReferencedClasses() : [],
		);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return array_merge(
			$this->min !== null ? $this->min->getReferencedTemplateTypes($positionVariance) : [],
			$this->max !== null ? $this->max->getReferencedTemplateTypes($positionVariance) : [],
		);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		$compare = static fn (?Type $left, ?Type $right) => $left === null ? $right === null : $right !== null && $left->equals($right);

		return $compare($this->min, $type->min) && $compare($this->max, $type->max);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'int<%s, %s>',
			$this->min === null ? 'min' : $this->min->describe($level),
			$this->max === null ? 'max' : $this->max->describe($level),
		);
	}

	public function isResolvable(): bool
	{
		return ($this->min === null || !TypeUtils::containsTemplateType($this->min))
			&& ($this->max === null || !TypeUtils::containsTemplateType($this->max));
	}

	protected function getResult(): Type
	{
		if ($this->min !== null && !$this->min->isInteger()->yes()) {
			return new ErrorType();

		}

		if ($this->max !== null && !$this->max->isInteger()->yes()) {
			return new ErrorType();
		}

		$min = $this->min instanceof ConstantIntegerType ? $this->min->getValue() : null;
		$max = $this->max instanceof ConstantIntegerType ? $this->max->getValue() : null;

		return IntegerRangeType::fromInterval($min, $max);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$min = $this->min !== null ? $cb($this->min) : null;
		$max = $this->max !== null ? $cb($this->max) : null;

		if ($this->min === $min && $this->max === $max) {
			return $this;
		}

		return new self($min, $max);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function toPhpDocNode(): TypeNode
	{
		if ($this->min === null) {
			$min = new IdentifierTypeNode('min');
		} else {
			$min = $this->min->toPhpDocNode();
		}

		if ($this->max === null) {
			$max = new IdentifierTypeNode('max');
		} else {
			$max = $this->max->toPhpDocNode();
		}

		return new GenericTypeNode(new IdentifierTypeNode('int'), [$min, $max]);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['min'],
			$properties['max'],
		);
	}

}
