<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;

class LazyUnionType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param Type[] $types
	 */
	public function __construct(private array $types)
	{
	}

	/**
	 * @return Type[]
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	public function getReferencedClasses(): array
	{
		return $this->resolve()->getReferencedClasses();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->resolve()->getReferencedTemplateTypes($positionVariance);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->resolve()->equals($type->resolve());
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->resolve()->describe($level);
	}

	public function isResolvable(): bool
	{
		return true;
	}

	protected function getResult(): Type
	{
		return TypeCombinator::union(...$this->getTypes());
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		return $this->resolve()->traverse($cb);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types']);
	}

}
