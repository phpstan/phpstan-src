<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;

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
		return sprintf(
			'%s[%s]',
			$this->type->describe($level),
			$this->offset->describe($level),
		);
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

		return new OffsetAccessType($type, $offset);
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
