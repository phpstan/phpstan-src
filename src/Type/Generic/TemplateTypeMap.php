<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class TemplateTypeMap
{

	private static ?TemplateTypeMap $empty = null;

	/** @var array<string,\PHPStan\Type\Type> */
	private array $types;

	/**
	 * @api
	 * @param array<string,\PHPStan\Type\Type> $types
	 */
	public function __construct(array $types)
	{
		$this->types = $types;
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([]);
		self::$empty = $empty;

		return $empty;
	}

	public function isEmpty(): bool
	{
		return count($this->types) === 0;
	}

	public function count(): int
	{
		return count($this->types);
	}

	/** @return array<string,\PHPStan\Type\Type> */
	public function getTypes(): array
	{
		return $this->types;
	}

	public function hasType(string $name): bool
	{
		return array_key_exists($name, $this->types);
	}

	public function getType(string $name): ?Type
	{
		return $this->types[$name] ?? null;
	}

	public function unsetType(string $name): self
	{
		if (!$this->hasType($name)) {
			return $this;
		}

		$types = $this->types;

		unset($types[$name]);

		if (count($types) === 0) {
			return self::createEmpty();
		}

		return new self($types);
	}

	public function union(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeCombinator::union($result[$name], $type);
			} else {
				$result[$name] = $type;
			}
		}

		return new self($result);
	}

	public function benevolentUnion(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeUtils::toBenevolentUnion(TypeCombinator::union($result[$name], $type));
			} else {
				$result[$name] = $type;
			}
		}

		return new self($result);
	}

	public function intersect(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeCombinator::intersect($result[$name], $type);
			} else {
				$result[$name] = $type;
			}
		}

		return new self($result);
	}

	/** @param callable(string,Type):Type $cb */
	public function map(callable $cb): self
	{
		$types = [];
		foreach ($this->types as $name => $type) {
			$types[$name] = $cb($name, $type);
		}

		return new self($types);
	}

	public function resolveToBounds(): self
	{
		return $this->map(static function (string $name, Type $type): Type {
			$type = TemplateTypeHelper::resolveToBounds($type);
			if ($type instanceof MixedType && $type->isExplicitMixed()) {
				return new MixedType(false);
			}

			return $type;
		});
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['types']
		);
	}

}
